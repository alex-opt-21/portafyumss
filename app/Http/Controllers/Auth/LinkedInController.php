<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OAuthUserService;
use Laravel\Socialite\Facades\Socialite;

class LinkedInController extends Controller
{
    public function __construct(private readonly OAuthUserService $oAuthUserService) {}

    public function redirect()
    {
        return Socialite::driver('linkedin-openid')
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $linkedinUser = Socialite::driver('linkedin-openid')
                ->stateless()
                ->user();

            $user = $this->oAuthUserService->resolveOrCreateUser(
                'linkedin',
                (string) $linkedinUser->getId(),
                $linkedinUser->getEmail(),
                $linkedinUser->user['given_name'] ?? $linkedinUser->getName() ?? 'Usuario',
                $linkedinUser->user['family_name'] ?? '',
            );

            $token = $this->oAuthUserService->issueToken($user);

            return response()
                ->view('linkedin-callback', [
                    'token' => $token,
                    'user' => $user,
                ])
                ->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
                ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
        } catch (\Exception $e) {
            return response()
                ->view('linkedin-callback', ['error' => $e->getMessage()])
                ->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
                ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
        }
    }
}
