<?php

namespace App\Services\Megaplan;

use App\Enums\ProjectType;
use App\Events\Notifications\PlanningMissing;
use App\Events\Notifications\PlanningApprovalRequired;

class MegaplanService
{
    protected $megaplanApiClient;

    public function __construct($host, $login, $password)
    {
        $this->megaplanApiClient = new MegaplanApiClient($host, $login, $password);
    }

    public function getCompletedChecklistItems($department, $project, $month, $year)
    {
        $checklist = [];
        $task = $this->getProjectMonthTask($department, $project, $month, $year);

        if (empty($task)) {
            return $checklist;
        }

        $response = $this->megaplanApiClient->getChecklistList('task', $task->Id);

        if (empty($response->data->items)) {
            return $checklist;
        }

        foreach ($response->data->items as $value) {
            if (!empty($value->IsDone)) {
                $checklist[] = $value->Title;
            }
        }

        return $checklist;
    }

    public function getProjectMonthTask($department, $project, $month, $year)
    {
        $searchQuery = $this->getTaskName($department, $project, $this->getMonthName($month), $year);

        $response = $this->megaplanApiClient->searchQuick($searchQuery);

        if ($response->status->code !== 'ok') {
            return null;
        }

        if (!property_exists($response->data, 'Tasks')) {
            return null;
        }

        foreach ($response->data->Tasks as $value) {
            if ($value->Status === 'cancelled') {
                continue;
            }

            // Дополнительная проверка на наличие названия месяца в названии тикета
            // Поиск мегаплана может возвращать тикеты за ненужные месяца
            if (mb_stripos($value->Name, $this->getMonthName($month)) === false) {
                continue;
            }

            if ($department === ProjectType::SEO_PROMOTION) {
                // Дополнительная проверка на наличие в названии тикета шаблонных приписок
                if (mb_stripos($value->Name, "мес") === false) {
                    continue;
                }
            }

            if (
                (property_exists($value, 'SuperTask') && is_object($value->SuperTask) && $value->SuperTask->Id === (int) $project['megaplan']) ||
                (property_exists($value, 'Project') && is_object($value->Project) && $value->Project->Id === (int) $project['megaplan'])
            ) {
                return $value;
            }
        }

        return null;
    }

    private function getTaskName($department, $project, $monthName, $year)
    {
        $domain = $this->normalizeDomain($project['domen']);
        $tokens = [$department, $domain, $monthName, $year];
        if ($project['ticket_search_query_suffix']) {
            $tokens[] = $project['ticket_search_query_suffix'];
        }
        return implode(' ', $tokens);
    }

    private function normalizeDomain($domain)
    {
        return preg_replace('/^www.(.+)/', '$1', $domain);
    }

    /**
     * Get the name of a month given its number
     * 
     * @param int $monthNum The number of the month
     * @return string The name of the month
     */
    private function getMonthName(int $monthNum)
    {
        $monthNames = [
            1 => 'Январь',
            'Февраль',
            'Март',
            'Апрель',
            'Май',
            'Июнь',
            'Июль',
            'Август',
            'Сентябрь',
            'Октябрь',
            'Ноябрь',
            'Декабрь'
        ];

        return $monthNames[$monthNum];
    }

    public function getTasksFromComments($department, $project, $month, $year)
    {
        $tasks = [];

        $ticket = $this->getProjectMonthTask($department, $project, $month, $year);
        if (empty($ticket)) {
            return $tasks;
        }

        // TODO: id или Id? --- IGNORE ---
        $tasks = $this->getTasksFromTicketComments($ticket->Id);

        $subTickets = $this->megaplanApiClient->getSubTasks($ticket->id);
        foreach ($subTickets as $subTicket) {
            $tasks = array_merge($tasks, $this->getTasksFromTicketComments($subTicket->id));
        }

        return $tasks;
    }

        /**
     * Retrieves tasks from the comments of a ticket.
     *
     * @param int $ticketId The ID of the ticket.
     *
     * @return array An array containing the tasks retrieved from the comments.
     */
    private function getTasksFromTicketComments($ticketId)
    {
        $tasks = [];
        $comments = $this->megaplanApiClient->getCommentList('task', $ticketId); //getCommentsBySubject(MegaplanSubjectType::TASK->value, $ticketId);
        
        foreach ($comments as $comment) {
            $text = $comment->Text;
            $tasks = array_merge($tasks, $this->parseTasksFromComment($text));
        }

        $tasks = array_unique($tasks);
        return $tasks;
    }

    private function parseTasksFromComment($text)
    {
        $delim = '💪';
        $tasks = explode($delim, $text);
        array_shift($tasks);
        $tasks = array_filter($tasks);
        return $tasks;
    }

    /**
     * Проверяет наличие месячного тикета и дергает уведомления:
     * - PlanningMissing, если тикета нет;
     * - PlanningApprovalRequired, если тикет найден, но требует согласования.
     *
     * @param string $department  Отдел/тип проекта (см. ProjectType)
     * @param array  $project     Ассоциативный массив проекта (тот же, что ты передаешь в getProjectMonthTask)
     * @param int    $month       Номер месяца
     * @param int    $year        Год
     * @param int    $projectId   ID проекта в нашей базе (для payload уведомления)
     * @param string $projectName Название проекта (для payload уведомления)
     */
    public function dispatchPlanningEvents(string $department, array $project, int $month, int $year, int $projectId, string $projectName): void
    {
        $task = $this->getProjectMonthTask($department, $project, $month, $year);

        if (!$task) {
            event(new PlanningMissing(projectId: $projectId, projectName: $projectName));
            return;
        }

        // Минимальная эвристика: если статус "на согласовании" (или близко) — требуем аппрув.
        $requiresApproval = false;

        if (property_exists($task, 'Status')) {
            $status = mb_strtolower((string)$task->Status);
            $requiresApproval = in_array($status, [
                'на согласовании',
                'ожидает согласования',
                'approval_required',
                'awaiting_approval',
                'wait_approval',
            ], true);
        }

        // Если есть явный флаг Approved/IsApproved — учитываем его.
        if (!$requiresApproval && (property_exists($task, 'Approved') || property_exists($task, 'IsApproved'))) {
            $approved = property_exists($task, 'Approved') ? (bool)$task->Approved : (bool)$task->IsApproved;
            $requiresApproval = !$approved;
        }

        if ($requiresApproval) {
            event(new PlanningApprovalRequired(projectId: $projectId, projectName: $projectName));
        }
    }
}
