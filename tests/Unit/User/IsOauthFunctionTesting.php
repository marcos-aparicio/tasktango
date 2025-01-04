<?php

namespace Tests\Unit\User;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class IsOauthFunctionTesting extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_to_fail_password_filled_with_provider_details_too (): void
    {
        $userOAuth = new User([
            'provider' => 'google',
            'provider_id' => '123456',
            'provider_token' => 'xyz_token',
            'password' => 'this should fail',
        ]);

        // Assert that the user is recognized as an OAuth account
        $this->assertFalse($userOAuth->isOauthAccount());
    }
    }
}
