<?php

namespace App\Livewire\SystemSettings\Users;

use App\Livewire\Forms\SystemSettings\Users\UserForm;
use App\Models\User;
use App\Services\RateService;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class UsersEdit extends Component
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

        if ($this->form->photo) {
            $this->form->image_path = $this->form->photo->store('user_photos', 'public');
        }

        $userService->update($this->form->id, $this->form->toArray());

        session()->flash('success', 'Пользователь успешно обновлен!');
        return redirect()->route('system-settings.users');
    }

    public function render()
    {
        return view('livewire.system-settings.users.users-edit', [
            'rates' => $this->rates,
        ]);
    }
}
