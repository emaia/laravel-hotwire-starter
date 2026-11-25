<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\TwoFactorConfirmedResponse as ConfirmedResponse;
use Laravel\Fortify\Fortify;

final class TwoFactorConfirmedResponse implements ConfirmedResponse
{
    /** Show recovery codes immediately after activation without changing the JSON contract. */
    public function toResponse(mixed $request): JsonResponse|RedirectResponse
    {
        return $request->wantsJson()
            ? new JsonResponse('', 200)
            : redirect()->route('two-factor.recovery')->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }
}
