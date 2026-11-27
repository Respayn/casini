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
            'data' => 'present|array',
            'data.*.PaymentNumber' => 'required|string|max:50',
            'data.*.PaymentDate' => 'required|date',
            'data.*.InnPayer' => ['required', 'regex:/^(\d{10}|\d{12})$/'],
            'data.*.Total' => 'required|numeric|min:0',
            'data.*.invoices' => 'required_unless:data.*.Canceled,true|array|min:1',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->input('data');

        if (! is_array($data)) {
            return;
        }

        $this->merge([
            'data' => array_map(function ($payment) {
                if (is_array($payment) && isset($payment['InnPayer'])) {
                    $payment['InnPayer'] = preg_replace('/\D/', '', (string) $payment['InnPayer']);
                }

                return $payment;
            }, $data),
        ]);
    }

    public function authenticate(): void
    {
        $user = User::where('login', $this->input('login'))->first();

        if (! $user || ! Hash::check($this->input('password'), $user->password)) {
            abort(401, 'Invalid credentials');
        }
    }
}
