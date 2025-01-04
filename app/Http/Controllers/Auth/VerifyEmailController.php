<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $redirectToProfilePicture = config('constants.ask_profile_picture_after_account_creation');

        if ($request->user()->hasVerifiedEmail()) {
            return $redirectToProfilePicture
                ? redirect()->route('picture')
                : redirect()->intended(route('profile', absolute: false) . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $redirectToProfilePicture
            ? redirect()->route('picture')
            : redirect()->intended(route('profile', absolute: false) . '?verified=1');
    }
}
