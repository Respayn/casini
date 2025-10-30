<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] class extends Component {
    public int $step = 1;

    // Step 1
    #[Validate('required|string|max:255')]
    public string $firstName = '';

    #[Validate('required|string|max:255')]
    public string $lastName = '';

    #[Validate('required|string|max:255')]
    public string $agencyName = '';

    #[Validate('required|string|max:255')]
    public string $timezone = 'Москва';

    // Step 2
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|min:10|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8|max:255')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $passwordConfirmation = '';

    public function nextStep()
    {
        if ($this->step === 1) {
            foreach (['firstName', 'lastName', 'agencyName', 'timezone'] as $field) {
                $this->validateOnly($field);
            }
        }
        if ($this->step === 2) {
            foreach (['email', 'phone', 'password', 'passwordConfirmation'] as $field) {
                $this->validateOnly($field);
            }
        }
        $this->step++;
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function register()
    {
        $this->validate();

        // Например:
        // $user = User::create([
        //     'first_name'            => $this->firstName,
        //     'last_name'             => $this->lastName,
        //     'agency_name'           => $this->agencyName,
        //     'timezone'              => $this->timezone,
        //     'email'                 => $this->email,
        //     'phone'                 => $this->phone,
        //     'password'              => Hash::make($this->password),
        // ]);

        $this->step = 3;
    }
};
