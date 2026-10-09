<?php

namespace App\Livewire\Drs;

use App\Data\Payment\DrsOperationData;
use App\Data\Payment\DrsOperationFormData;
use App\Data\UserData;
use App\Enums\PermissionGroup;
use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Services\PaymentService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('ДРС')]
class extends Component
{
    use WithSidebarProjectFilter;

    public Carbon $month;

    public bool $onlyNew = false;

    public DrsOperationFormData $form;

    /** edit - правка операции, credit - новый кредит */
    public string $modalMode = 'edit';

    /** Поля окна на момент открытия: с ними сравниваем, есть ли изменения */
    public array $formSnapshot = [];

    public ?string $actionMessage = null;

    public string $actionMessageType = 'success';

    private PaymentService $paymentService;

    public function boot(PaymentService $paymentService): void
    {
        $this->paymentService = $paymentService;
    }

    public function mount(): void
    {
        $this->month = Carbon::now()->startOfMonth();
        $this->form = new DrsOperationFormData;
    }

    protected function afterSidebarProjectFilterChanged(): void
    {
        unset($this->operations, $this->totals);
    }

    public function updatedMonth(): void
    {
        $this->month = $this->month->copy()->startOfMonth();
        unset($this->operations, $this->totals);
    }

    public function updatedOnlyNew(): void
    {
        unset($this->operations, $this->totals);
    }

    public function updatedFormClientId(): void
    {
        $this->form->projectId = null;
        $this->form->managerId = $this->form->clientId === null
            ? null
            : $this->paymentService->getClientManagerId($this->form->clientId);
        $this->syncClientFee();
        $this->form->includeFeeDebt = false;
        unset($this->projectOptions, $this->creditDebt, $this->feeDebt);
    }

    public function updatedFormOperationDate(): void
    {
        $this->syncClientFee();
    }

    private function syncClientFee(): void
    {
        $this->form->feeIncluded = $this->form->clientId === null
            || $this->paymentService->isClientFeeIncluded($this->form->clientId, $this->form->operationDate);
    }

    public function updatedFormProjectId(): void
    {
        if ($this->form->projectId === 0) {
            $this->form->projectId = null;
        }
    }

    public function updatedFormCreditAmount(): void
    {
        if ($this->modalMode === 'credit') {
            $this->form->topUpAmount = round(abs($this->form->creditAmount), 2);
        } elseif (! $this->form->isManual) {
            $this->form->topUpAmount = round($this->form->bankAmount - $this->form->creditAmount, 2);
        }
    }

    public function updatedFormIsSentToCabinet(bool $value): void
    {
        if ($value && ! filled($this->form->sentDate)) {
            $this->form->sentDate = today()->toDateString();
        }
    }

    #[Computed]
    public function operations(): Collection
    {
        return $this->paymentService->getMonthOperations(
            Auth::user(),
            $this->month,
            $this->sidebarProjectId,
            $this->onlyNew
        );
    }

    /**
     * @return array{bank: float, credit: float, cabinet: float, fee: float}
     */
    #[Computed]
    public function totals(): array
    {
        return [
            'bank' => round($this->operations->sum(fn (DrsOperationData $row) => $row->bankAmount), 2),
            'credit' => round($this->operations->sum(fn (DrsOperationData $row) => $row->creditAmount), 2),
            'cabinet' => round($this->operations->sum(fn (DrsOperationData $row) => $row->adCabinetAmount), 2),
            'fee' => round($this->operations->sum(fn (DrsOperationData $row) => $row->feeAmount), 2),
        ];
    }

    #[Computed]
    public function lastImportLabel(): ?string
    {
        return $this->paymentService->getLastImportAt()?->format('d.m.Y, H:i');
    }

    #[Computed]
    public function timezoneLabel(): string
    {
        return $this->paymentService->getAgencyTimezoneLabel();
    }

    #[Computed]
    public function canEdit(): bool
    {
        return $this->hasAnyLevel(PermissionGroup::ADVERTISING_FUNDS_MOVEMENT, ['edit', 'full']);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return $this->hasAnyLevel(PermissionGroup::ADVERTISING_FUNDS_MOVEMENT, ['full']);
    }

    #[Computed]
    public function canSeeStatus(): bool
    {
        return $this->hasAnyLevel(PermissionGroup::ADVERTISING_FUNDS_MOVEMENT_STATUS, ['read', 'edit', 'full']);
    }

    #[Computed]
    public function canEditStatus(): bool
    {
        return $this->hasAnyLevel(PermissionGroup::ADVERTISING_FUNDS_MOVEMENT_STATUS, ['edit', 'full']);
    }

    #[Computed]
    public function canSeeInvoice(): bool
    {
        return $this->hasAnyLevel(PermissionGroup::ADVERTISING_FUNDS_MOVEMENT_INVOICE, ['read', 'edit', 'full']);
    }

    #[Computed]
    public function canEditInvoice(): bool
    {
        return $this->hasAnyLevel(PermissionGroup::ADVERTISING_FUNDS_MOVEMENT_INVOICE, ['edit', 'full']);
    }

    #[Computed]
    public function clientOptions(): Collection
    {
        return $this->paymentService->getClientOptions(Auth::user());
    }

    #[Computed]
    public function projectOptions(): Collection
    {
        if ($this->form->clientId === null) {
            return collect();
        }

        return collect([['id' => 0, 'name' => 'Без клиенто-проекта']])
            ->merge($this->paymentService->getClientProjectOptions($this->form->clientId));
    }

    #[Computed]
    public function managerOptions(): Collection
    {
        return app(UserService::class)->getManagers()
            ->map(fn (UserData $user) => [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->login,
            ])
            ->values();
    }

    #[Computed]
    public function advertisingSystemOptions(): array
    {
        return $this->paymentService->getAdvertisingSystemOptions();
    }

    #[Computed]
    public function creditDebt(): float
    {
        return $this->form->clientId === null ? 0.0 : $this->paymentService->getClientCreditDebt($this->form->clientId);
    }

    #[Computed]
    public function feeDebt(): float
    {
        if ($this->form->clientId === null) {
            return 0.0;
        }

        return $this->paymentService->getClientFeeDebt($this->form->clientId, $this->form->id);
    }

    /**
     * @return array{fee: float, cabinet: float, includedFeeDebt: float}
     */
    #[Computed]
    public function calculation(): array
    {
        return $this->paymentService->calculate($this->form, $this->feeDebt);
    }

    public function openOperation(int $operationId): void
    {
        $form = $this->paymentService->openOperation(Auth::user(), $operationId);

        if ($form === null) {
            $this->setActionMessage('Операция не найдена или недоступна', 'error');

            return;
        }

        $this->resetErrorBag();
        $this->modalMode = 'edit';
        $this->form = $form;
        $this->formSnapshot = $form->toArray();
        $this->forgetFormComputed();
        unset($this->operations);
        $this->dispatch('modal-show', name: 'drs-operation-modal');
    }

    public function openCredit(): void
    {
        abort_unless($this->canEdit, 403);

        $this->resetErrorBag();
        $this->modalMode = 'credit';
        $this->form = $this->paymentService->newCreditForm();
        $this->formSnapshot = $this->form->toArray();
        $this->forgetFormComputed();
        $this->dispatch('modal-show', name: 'drs-operation-modal');
    }

    public function setIncludeFeeDebt(bool $value): void
    {
        $this->form->includeFeeDebt = $value;
    }

    public function save(): void
    {
        $can = $this->permissionsForSave();

        if ($this->modalMode === 'credit') {
            abort_unless($can['edit'], 403);
            $this->paymentService->createCredit(Auth::user(), $this->form, $can);
            $this->setActionMessage('Кредит клиенту сохранен', 'success');
        } else {
            abort_unless($can['edit'] || $can['status'] || $can['invoice'], 403);
            $this->paymentService->saveOperation(Auth::user(), $this->form, $can);
            $this->setActionMessage('Операция сохранена', 'success');
        }

        $this->dispatch('modal-hide', name: 'drs-operation-modal');
        unset($this->operations, $this->totals);
    }

    public function toggleStatus(int $operationId, bool $value): void
    {
        abort_unless($this->canEditStatus, 403);
        $this->paymentService->toggleFlag(Auth::user(), $operationId, 'is_sent_to_cabinet', $value);
        unset($this->operations);
    }

    public function togglePiggyBank(int $operationId, bool $value): void
    {
        abort_unless($this->canEdit, 403);
        $this->paymentService->toggleFlag(Auth::user(), $operationId, 'is_fee_in_piggy_bank', $value);
        unset($this->operations);
    }

    public function toggleInvoice(int $operationId, bool $value): void
    {
        abort_unless($this->canEditInvoice, 403);
        $this->paymentService->toggleFlag(Auth::user(), $operationId, 'is_invoice_issued', $value);
        unset($this->operations);
    }

    public function hideOperation(int $operationId): void
    {
        abort_unless($this->canDelete, 403);
        $this->paymentService->hideOperation(Auth::user(), $operationId);
        $this->setActionMessage('Операция удалена из ДРС', 'success');
        unset($this->operations, $this->totals);
    }

    /**
     * @return array{edit: bool, status: bool, invoice: bool}
     */
    private function permissionsForSave(): array
    {
        return [
            'edit' => $this->canEdit,
            'status' => $this->canEditStatus,
            'invoice' => $this->canEditInvoice,
        ];
    }

    /**
     * @param  list<string>  $levels
     */
    private function hasAnyLevel(PermissionGroup $group, array $levels): bool
    {
        return Auth::user()->hasAnyPermission(
            array_map(fn (string $level) => $level.' '.$group->value, $levels)
        );
    }

    private function forgetFormComputed(): void
    {
        unset($this->projectOptions, $this->creditDebt, $this->feeDebt, $this->calculation);
    }

    private function setActionMessage(string $message, string $type): void
    {
        $this->actionMessage = $message;
        $this->actionMessageType = $type;
    }
};
