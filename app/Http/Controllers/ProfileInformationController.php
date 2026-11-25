<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\ProfileInformationUpdatedResponse;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\ProfileInformationController as FortifyController;

class ProfileInformationController extends FortifyController
{
    /** Render only the authenticated account's profile. */
    public function edit(#[CurrentUser] User $user): View
    {
        abort_unless(Features::enabled(Features::updateProfileInformation()), 404);

        return view('account.profile', compact('user'));
    }

    /** Validate through the updater before canonicalizing untrusted email input. */
    public function update(Request $request, UpdatesUserProfileInformation $updater): ProfileInformationUpdatedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $updater->update($user, $request->all());

        return app(ProfileInformationUpdatedResponse::class);
    }
}
