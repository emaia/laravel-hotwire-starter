<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the dashboard identifies the signed-in user and links to account security', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('aria-label="'.__('Account menu').'"', false)
        ->assertSee($user->name)->assertSee($user->email)
        ->assertSee(route('account.password.edit'), false)
        ->assertSee(route('two-factor.settings'), false);
});

test('the account menu escapes user-provided identity', function (): void {
    $user = User::factory()->create(['name' => '<script>alert("name")</script>']);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee($user->name)
        ->assertDontSee($user->name, false);
});
