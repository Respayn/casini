<?php

namespace App\Support;

/**
 * Одно правило пароля для создания / смены / регистрации / сброса / приглашения.
 */
final class UserPasswordRules
{
    public const MIN = 6;

    public const RULE_MIN = 'min:6';

    public const RULE_REGEX = 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/';

    public const MESSAGE_MIN = 'слишком короткий пароль';

    public const MESSAGE_REGEX = 'пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры';

    /**
     * @return list<string>
     */
    public static function passwordConstraints(): array
    {
        return [
            'required',
            self::RULE_MIN,
            self::RULE_REGEX,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function changeRules(string $prefix = ''): array
    {
        $passwordField = $prefix.'password';

        return [
            $prefix.'current_password' => [
                'required',
                'string',
            ],
            $passwordField => self::passwordConstraints(),
            $prefix.'password_confirmation' => [
                'required',
                'same:'.$passwordField,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function changeMessages(string $prefix = ''): array
    {
        return [
            $prefix.'current_password.required' => 'Поле текущий пароль обязательно для заполнения',
            $prefix.'password.required' => 'Поле пароль обязательно для заполнения',
            $prefix.'password.min' => self::MESSAGE_MIN,
            $prefix.'password.regex' => self::MESSAGE_REGEX,
            $prefix.'password_confirmation.required' => 'Поле повторите пароль обязательно для заполнения',
            $prefix.'password_confirmation.same' => 'пароли не совпадают',
        ];
    }

    public static function isValid(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $password);
    }

    public static function pairIsReady(string $password, string $confirmation): bool
    {
        return self::isValid($password) && $password === $confirmation;
    }
}
