<?php

use App\Services\Auth\LoginService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')]
class extends Component {
    #[Validate('required|string')]
    public string $userLogin = '';

    #[Validate('required|string')]
    public string $password = '';

    public function login(LoginService $loginService): void
    {
        $this->validate();

        $loginService->attempt($this->userLogin, $this->password);

        Session::regenerate();

        $default = route('system-settings.dictionaries', absolute: false);
        $intended = session()->pull('url.intended', $default);

        if ($this->shouldIgnoreIntendedRedirect($intended)) {
            $intended = $default;
        }

        // Полная перезагрузка страницы — иначе после regenerate() Livewire navigate
        // может не подхватить новую сессию и вернуть на главную.
        $this->redirect($intended, navigate: false);
    }

    private function shouldIgnoreIntendedRedirect(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        $ignoredPaths = [
            '/',
            route('landing', absolute: false),
            route('login', absolute: false),
            route('register', absolute: false),
            route('password.request', absolute: false),
        ];

        return in_array($path, $ignoredPaths, true);
    }
};
