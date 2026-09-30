<?php

namespace App\Livewire\SystemSettings\Users;

use App\Livewire\Forms\SystemSettings\Users\UserForm;
use App\Models\User;
use App\Services\RateService;
use App\Services\RoleService;
use App\Services\UserService;
use App\Support\SystemSettingsSectionPermissions;
use App\Support\UserProfileAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Exceptions\UnauthorizedException;

new
#[Layout('layouts::system-settings')]
class extends Component
{
    use WithFileUploads;

    public UserForm $form;

    public Collection $rates;

    public array $roles = [];

    public User $user;

    public function mount(
        RateService $ratesService,
        RoleService $roleService,
        User $user
    ) {
        $this->user = $user;
        $this->form->from($user);
        $this->rates = $ratesService->getRates();
        $this->roles = $roleService->getRoleOptions();
    }

    public function rendering($view): void
    {
        $view->title(
            Auth::id() === $this->user->id
                ? 'Настройки профиля'
                : 'Редактировать пользователя'
        );
    }

    #[Computed]
    public function isOwnProfile(): bool
    {
        return UserProfileAccess::isOwnProfile($this->user);
    }

    #[Computed]
    public function canEditUserAdminFields(): bool
    {
        return UserProfileAccess::canEditAdminFields();
    }

    #[Computed]
    public function isSaveReady(): bool
    {
        if (! $this->form->isEmailValid() || $this->getErrorBag()->has('form.email')) {
            return false;
        }

        if (! $this->form->hasPasswordChange()) {
            return true;
        }

        return filled(trim((string) $this->form->current_password))
            && (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', (string) $this->form->password)
            && $this->form->password === $this->form->password_confirmation;
    }

    public function validateFormField(string $field): void
    {
        $this->form->validateOnly($field);
    }

    public function validatePasswordField(string $field): void
    {
        if (! $this->form->hasPasswordChange()) {
            $this->resetErrorBag("form.{$field}");

            return;
        }

        $this->form->validateOnly(
            $field,
            $this->form->passwordChangeRules(),
            $this->form->passwordChangeMessages(),
        );
    }

    public function save(UserService $userService)
    {
        if (! UserProfileAccess::canSaveUser($this->user)) {
            throw UnauthorizedException::forPermissions(
                SystemSettingsSectionPermissions::editPermissionNames(
                    SystemSettingsSectionPermissions::users()
                )
            );
        }

        $this->form->validate();

        $passwordChanged = $this->form->hasPasswordChange();

        if ($passwordChanged) {
            $this->form->validate(
                $this->form->passwordChangeRules(),
                $this->form->passwordChangeMessages(),
            );

            if (! Hash::check((string) $this->form->current_password, $this->user->password)) {
                throw ValidationException::withMessages([
                    'form.current_password' => 'Неверный текущий пароль',
                ]);
            }
        }

        // TODO: Вынести в репозиторий
        try {
            // Если пользователь запросил удаление фото
            if ($this->form->delete_photo && empty($this->form->photo)) {
                if ($this->user->image_path) {
                    Storage::disk('public')->delete($this->user->image_path);
                }
                $this->form->image_path = null;
            }
            // Если загружено новое фото
            elseif ($this->form->photo) {
                if ($this->user->image_path) {
                    Storage::disk('public')->delete($this->user->image_path);
                }
                $this->form->image_path = $this->form->photo->store('user_photos', 'public');
            } else {
                // Фото не меняли — оставить прежний путь
                $this->form->image_path = $this->user->image_path;
            }
        } catch (\Error $exception) {
            dd($exception);
        }

        $data = $this->form->toArray();
        unset(
            $data['current_password'],
            $data['password_confirmation'],
            $data['photo'],
            $data['delete_photo'],
            $data['id'],
        );

        if (! $passwordChanged) {
            unset($data['password']);
        }

        $data = UserProfileAccess::mergeSavePayload($this->user, $data);

        if ($this->canEditUserAdminFields) {
            $data = $this->form->applyAccountStatus($data, $this->user);
        } else {
            unset($data['account_status']);
        }

        $userService->update($this->form->id, $data);

        $this->form->clearPasswordFields();
        $this->user = $this->user->fresh();
        $this->form->from($this->user);

        if ($passwordChanged) {
            session()->flash('password_updated', 'Пароль успешно обновлен');
        } else {
            session()->flash(
                'success',
                UserProfileAccess::isOwnProfile($this->user)
                    ? 'Профиль успешно обновлен!'
                    : 'Пользователь успешно обновлен!'
            );
        }

        return $this->redirect(
            route('system-settings.users.edit', $this->user),
            navigate: true
        );
    }

    public function cancelChanges(): mixed
    {
        return $this->redirect(
            route('system-settings.users.edit', $this->user),
            navigate: true
        );
    }

    public function deletePhoto()
    {
        if ($this->user->image_path) {
            Storage::disk('public')->delete($this->user->image_path);
        }
        $this->form->image_path = null;
        $this->form->photo = null;
    }
};
