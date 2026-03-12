<?php

namespace Src\Domain\Users;

use Exception;

class UserNotFoundException extends Exception
{
    protected $message = 'Пользователь не найден';
}