<?php

namespace App\Services;

use App\Data\Sidebar\EmployeeData;
use App\Enums\Role;
use App\Models\User;
use Spatie\LaravelData\DataCollection;
use Str;

class SidebarService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getEmployees(?string $sortBy, ?string $searchQuery): array
    {
        // TODO: add repositories
        $employeesQuery = User::query();

        // TODO: to enum
        // if ($sortBy === 'manager') {
        //     $employeesQuery = $employeesQuery->role([Role::MANAGER, Role::MANAGER_DEPARTMENT_HEAD, Role::OFFICE_MANAGER]);
        // } else if ($sortBy === 'seo') {
        //     $employeesQuery = $employeesQuery->role([Role::SEO_DEPARTMENT_HEAD, Role::SEO_SPECIALIST]);
        // } else if ($sortBy === 'ppc') {
        //     $employeesQuery = $employeesQuery->role([Role::CA_DEPARTMENT_HEAD, Role::CA_SPECIALIST]);
        // }

        // if (!empty($searchQuery)) {
        //     $employeesQuery->where(function ($query) use ($searchQuery) {
        //         $query->where('first_name', 'like', "%{$searchQuery}%")
        //             ->orWhere('last_name', 'like', "%{$searchQuery}%")
        //             ->orWhereHas('clients', function ($clientQuery) use ($searchQuery) {
        //                 $clientQuery->where('name', 'like', "%{$searchQuery}%")
        //                     ->orWhereHas('projects', function ($projectQuery) use ($searchQuery) {
        //                         $projectQuery->where('name', 'like', "%{$searchQuery}%");
        //                     });
        //             });
        //     });
        // }

        // $employees = $employeesQuery
        //     ->with('clients.projects')
        //     ->orderBy('first_name')
        //     ->orderBy('last_name')
        //     ->get()
        //     ->filter();

        // if (empty($searchQuery)) {
        //     return EmployeeData::collect($employees->map(function ($employee) {
        //         return [
        //             'id' => $employee->id,
        //             'name' => $employee->full_name,
        //             'clients' => $employee->clients->map(function ($client) {
        //                 return [
        //                     'id' => $client->id,
        //                     'name' => $client->name,
        //                     'projects' => $client->projects->map(function ($project) {
        //                         return [
        //                             'id' => $project->id,
        //                             'name' => $project->name,
        //                         ];
        //                     })->keyBy('id')->toArray(),
        //                 ];
        //             })->keyBy('id')->toArray(),
        //         ];
        //     })->keyBy('id')->toArray());
        // }


        // // Фильтрация данных
        // $employees = $employees->map(function ($employee) use ($searchQuery) {
        //     $clients = $employee->clients->map(function ($client) use ($employee, $searchQuery) {
        //         $projects = $client->projects;

        //         // Если searchQuery найден в имени клиента, возвращаем только этого клиента
        //         if (Str::contains(Str::lower($client->name), Str::lower($searchQuery))) {
        //             return [
        //                 'id' => $client->id,
        //                 'name' => $client->name,
        //                 'projects' => $projects->map(function ($project) {
        //                     return [
        //                         'id' => $project->id,
        //                         'name' => $project->name,
        //                     ];
        //                 })->keyBy('id')->toArray(),
        //             ];
        //         }

        //         // Если searchQuery найден в названии проекта, возвращаем только этот проект
        //         $filteredProjects = $projects->filter(function ($project) use ($searchQuery) {
        //             return Str::contains(Str::lower($project->name), Str::lower($searchQuery));
        //         });

        //         if ($filteredProjects->isNotEmpty()) {
        //             return [
        //                 'id' => $client->id,
        //                 'name' => $client->name,
        //                 'projects' => $filteredProjects->map(function ($project) {
        //                     return [
        //                         'id' => $project->id,
        //                         'name' => $project->name,
        //                     ];
        //                 })->keyBy('id')->toArray(),
        //             ];
        //         }

        //         // Если searchQuery найден в имени или фамилии сотрудника, возвращаем всех клиентов
        //         if (Str::contains(Str::lower($employee->first_name), Str::lower($searchQuery)) || Str::contains(Str::lower($employee->last_name), Str::lower($searchQuery))) {
        //             return [
        //                 'id' => $client->id,
        //                 'name' => $client->name,
        //                 'projects' => $projects->map(function ($project) {
        //                     return [
        //                         'id' => $project->id,
        //                         'name' => $project->name,
        //                     ];
        //                 })->keyBy('id')->toArray(),
        //             ];
        //         }

        //         return null;
        //     })->filter()->values();

        //     return [
        //         'id' => $employee->id,
        //         'name' => $employee->full_name,
        //         'clients' => $clients->keyBy('id')->toArray(),
        //     ];
        // })->filter(function ($employee) {
        //     return !empty($employee['clients']);
        // })->values();

        // return EmployeeData::collect($employees->keyBy('id')->toArray());

        return EmployeeData::collect([

        ]);
    }
}
