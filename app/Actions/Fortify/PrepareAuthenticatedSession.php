<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession as FortifyPrepare;

final class PrepareAuthenticatedSession extends FortifyPrepare
{
    /**
     * Require explicit password confirmation before entering security settings.
     *
     * @param  Request  $request
     * @param  callable  $next
     */
    public function handle(mixed $request, mixed $next): mixed
    {
        $request->session()->forget('auth.password_confirmed_at');

        return parent::handle($request, $next);
    }
}
