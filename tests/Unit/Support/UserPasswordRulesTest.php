<?php

namespace Tests\Unit\Support;

use App\Support\UserPasswordRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserPasswordRulesTest extends TestCase
{
    public function test_rejects_short_password_and_password_without_digit(): void
    {
        $this->assertFalse(UserPasswordRules::isValid('Ab1'));
        $this->assertFalse(UserPasswordRules::isValid('abcdef'));
        $this->assertTrue(UserPasswordRules::isValid('abcdef1'));
        $this->assertFalse(UserPasswordRules::pairIsReady('abcdef1', 'abcdef2'));
        $this->assertTrue(UserPasswordRules::pairIsReady('abcdef1', 'abcdef1'));
    }

    public function test_user_form_and_auth_screens_share_the_same_rule_set(): void
    {
        $this->assertSame(
            UserPasswordRules::passwordConstraints(),
            UserPasswordRules::changeRules()['password'],
        );

        $this->assertContains(UserPasswordRules::RULE_MIN, UserPasswordRules::passwordConstraints());
        $this->assertContains(UserPasswordRules::RULE_REGEX, UserPasswordRules::passwordConstraints());

        $short = Validator::make(
            ['password' => 'Ab1'],
            ['password' => UserPasswordRules::passwordConstraints()],
        );
        $this->assertTrue($short->fails());

        $noDigit = Validator::make(
            ['password' => 'abcdef'],
            ['password' => UserPasswordRules::passwordConstraints()],
        );
        $this->assertTrue($noDigit->fails());

        $ok = Validator::make(
            ['password' => 'abcdef1'],
            ['password' => UserPasswordRules::passwordConstraints()],
        );
        $this->assertFalse($ok->fails());
    }
}
