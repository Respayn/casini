<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class PaymentRequest extends FormRequest
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
            'data.*.PaymentNumber' => 'required|string|max:50',
            'data.*.PaymentDate' => 'required|date',
            'data.*.InnPayer' => 'required|digits:10',
            'data.*.Total' => 'required|numeric|min:0',
            'data.*.invoices' => 'required|array|min:1'
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
