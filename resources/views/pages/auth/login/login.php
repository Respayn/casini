<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')]
class extends Component {
    #[Validate('required|string')]
    public string $userLogin = '';

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (!Auth::attempt(['login' => $this->userLogin, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'userLogin' => __('auth.failed'),
            ]);
        }

        Session::regenerate();

        $this->redirectIntended(default: route('system-settings.dictionaries', absolute: false), navigate: true);
    }
};
