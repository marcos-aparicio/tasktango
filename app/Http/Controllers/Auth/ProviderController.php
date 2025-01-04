<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ProviderId;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect($provider)
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            return redirect('/login')
                ->withErrors([
                    'form.email' => 'Error logging in. Please try again later.',
                    'oauth' => 'Error logging in. Please try again later.'
                ]);
        }
    }

    public function callback($provider)
    {
        $routeToRedirect = session()->get('redirectURL', '/login');
        try {
            $social_user = Socialite::driver($provider)->user();
            $possible_user = User::where('email', $social_user->getEmail());
            if ($possible_user->exists() && $possible_user->first()->provider !== $provider) {
                return redirect($routeToRedirect)
                    ->withErrors([
                        'form.email' => 'This email uses another login method.',
                        'email' => 'This email uses another login method.'
                    ]);
            }

            $user = User::where([
                'provider' => $provider,
                'provider_id' => $social_user->getId(),
            ])->first();

            if (!$user) {
                // saving the provider id in a separate table to prevent user impersonation(trying to create a user from the insert username page without having an actual oauth account by just faking the id)
                ProviderId::create(['valid_provider_id' => $social_user->getId()]);
                $data = [
                    'full_name' => $social_user->getName(),
                    'username' => $social_user->getNickname() ?? $social_user->getName(),
                    'email' => $social_user->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $social_user->getId(),
                    'provider_token' => $social_user->token,
                ];

                $imgContent = file_get_contents($social_user->getAvatar());
                $imgPath = 'profile_pictures/' . $social_user->getId() . '.jpg';

                Storage::disk('public')->put($imgPath, $imgContent);
                $data['profile_picture'] = $imgPath;

                $validator = Validator::make($data, User::getRules());

                if ($validator->fails()) {
                    Log::error($validator->errors());
                    return redirect($routeToRedirect)->withErrors([
                        // this is for the login page
                        'form.email' => 'Error with this authentication method. Please try again later with another account or another method.',
                        // this is for the register page
                        'oauth' => "Couldn't register with this account using " . ucfirst($provider) . '. Please try again later with another account or another method.'
                    ]);
                }

                $user = User::create($data);
                $user->email_verified_at = now();
                // the provider will give us a profile picture so there's no need to ask the user again
                $user->has_asked_for_profile_picture = true;
                event(new Registered($user));
                $user->save();
            }
            $user->provider_token = $social_user->token;
            $user->save();
            Auth::login($user);
            return redirect('/');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()
                ->route('login')
                ->withErrors(['form.email' => 'Error logging in. Please try again later.']);
        }
    }
}
