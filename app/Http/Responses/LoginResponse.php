<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if (Auth::user()?->role === null) {
            return redirect()->route('onboarding.role');
        }

        return $request->wantsJson()
            ? response()->noContent()
            : redirect()->intended(route('dashboard', absolute: false));
    }
}
