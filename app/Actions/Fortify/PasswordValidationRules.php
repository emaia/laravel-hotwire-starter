<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /** @return array<int, string|Password> */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'max:1024', 'confirmed'];
    }
}
