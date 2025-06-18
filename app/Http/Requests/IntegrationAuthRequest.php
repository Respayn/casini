<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class IntegrationAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
            'data' => 'required|array',
            'data.*.actNumber' => 'required|string|max:50',
            'data.*.actDate' => 'required|date',
            'data.*.company.inn' => 'required|digits:10',
            'data.*.total' => 'required|numeric|min:0',
            'data.*.items' => 'required|array|min:1'
        ];
    }

    public function authenticate(): void
    {
        $user = User::where('login', $this->input('login'))->first();

        if (!$user || !Hash::check($this->input('password'), $user->password)) {
            abort(401, 'Invalid credentials');
        }
    }
}
