<?php

namespace App\Livewire\SystemSettings\Users;

use App\Livewire\Forms\SystemSettings\Users\UserForm;
use App\Models\User;
use App\Services\RateService;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new
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
        User $user)
    {
        $this->form->from($user);
        $this->rates = $ratesService->getRates();
        $this->roles = $roleService->getRoleOptions();
    }

    public function save(UserService $userService)
    {
        $this->form->validate();

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

        $userService->update($this->form->id, $this->form->toArray());

        session()->flash('success', 'Пользователь успешно обновлен!');
        return redirect()->route('system-settings.users');
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
