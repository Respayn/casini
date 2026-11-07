<?php

namespace App\Livewire\SystemSettings\Users;

use App\Enums\UserAccountStatus;
use App\Livewire\Forms\SystemSettings\Users\UserForm;
use App\Models\User;
use App\Services\Auth\UserInvitationService;
use App\Services\RateService;
use App\Services\RoleService;
use App\Services\UserService;
use App\Support\SystemSettingsSectionPermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Exceptions\UnauthorizedException;

new
#[Layout('layouts::system-settings')]
#[Title('Добавить пользователя')]
class extends Component
{
    use WithFileUploads;

    public UserForm $form;

    public Collection $rates;

    public array $roles = [];

    public function mount(): void
    {
        $this->form->account_status = UserAccountStatus::PendingEmail->value;
    }

    public function boot(
        RateService $ratesService,
        RoleService $roleService
    ) {
        $this->rates = $ratesService->getRates(); // Получить список ставок для селекта
        // Получение ролей для выпадающего списка, если нужно
        $this->roles = $roleService->getRoleOptions();
    }

    #[Computed]
    public function isSaveReady(): bool
    {
        if (! filled(trim((string) $this->form->login))) {
            return false;
        }

        if (! filled(trim((string) $this->form->first_name))) {
            return false;
        }

        if (! filled($this->form->role_id)) {
            return false;
        }

        if (! filled((string) $this->form->account_status)) {
            return false;
        }

        if (! $this->form->isEmailValid() || $this->getErrorBag()->has('form.email')) {
            return false;
        }

        return true;
    }

    public function validateFormField(string $field): void
    {
        $this->form->validateOnly($field);
    }

    public function updatedFormPhoto(): void
    {
        $this->form->guardPhoto();
    }

    public function save(UserService $userService, UserInvitationService $invitations)
    {
        if (! SystemSettingsSectionPermissions::userCanEdit(
            SystemSettingsSectionPermissions::users()
        )) {
            throw UnauthorizedException::forPermissions(
                SystemSettingsSectionPermissions::editPermissionNames(
                    SystemSettingsSectionPermissions::users()
                )
            );
        }

        $this->form->account_status = UserAccountStatus::PendingEmail->value;
        $this->validate();

        // TODO: Вынести в репозиторий
        if ($this->form->photo) {
            $this->form->image_path = $this->form->photo->store('user_photos', 'public');
        }

        $currentAgencyId = session('current_agency_id') ?? (Auth::user()->agency_id ?? null);

        $userData = [
            'login' => $this->form->login,
            'first_name' => $this->form->first_name,
            'last_name' => $this->form->last_name,
            'email' => $this->form->email,
            'phone' => $this->form->phone,
            'image_path' => $this->form->image_path,
            'bitrix24_id' => $this->form->bitrix24_id,
            'rate_id' => $this->form->rate_id,
            'role_id' => $this->form->role_id,
            'enable_important_notifications' => $this->form->enable_notifications,
            'enable_notifications' => $this->form->enable_notifications,
            'agency_id' => $currentAgencyId,
        ];

        $userData = $this->form->applyAccountStatus($userData);

        // Создаём пользователя через сервис
        $created = $userService->create($userData);
        $user = User::query()->findOrFail($created->id);
        $invitations->send($user);

        session()->flash('success', 'Пользователь создан. Инструкции отправлены на email.');

        return redirect()->route('system-settings.users');
    }

    public function cancelChanges(): mixed
    {
        return $this->redirect(route('system-settings.users'), navigate: true);
    }

    public function deletePhoto()
    {
        // Просто обнуляем фото в форме (Livewire сам удалит temporaryUrl)
        $this->form->photo = null;
        $this->form->image_path = null;
    }
};
