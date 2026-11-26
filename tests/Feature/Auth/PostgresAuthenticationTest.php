<?php

use App\Actions\Fortify\AccountCredentials;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requires PostgreSQL.');
    }
});

test('a credential change cannot enter its mutation while another connection locks the account', function (): void {
    // Start Laravel's test transaction, then insert through the other connection so both can see the row.
    DB::select('select 1');
    config(['database.connections.lock_holder' => [...DB::connection()->getConfig(), 'name' => 'lock_holder']]);
    $holder = DB::connection('lock_holder');
    $user = User::factory()->make()->setConnection('lock_holder');
    $user->save();

    try {
        $holder->beginTransaction();
        $holder->table('users')->where('id', $user->id)->lockForUpdate()->first();

        $account = User::query()->findOrFail($user->id);
        DB::statement("SET LOCAL lock_timeout = '250ms'");
        expect(fn () => app(AccountCredentials::class)->change($account, function (): void {
            $this->fail('The mutation ran while another connection held the account lock.');
        }))->toThrow(QueryException::class, 'SQLSTATE[55P03]');
    } finally {
        $holder->rollBack();
        $user->delete();
        DB::purge('lock_holder');
    }
});

test('the password reset throttle persists across cache instances in PostgreSQL', function (): void {
    RateLimiter::swap(new CacheRateLimiter(Cache::store('database')));
    Notification::fake();
    $user = User::factory()->create();
    $payload = ['email' => $user->email];

    $this->postJson(route('password.email'), $payload)->assertSuccessful();
    $this->postJson(route('password.email'), $payload)->assertUnprocessable();

    Cache::forgetDriver('database');
    RateLimiter::swap(new CacheRateLimiter(Cache::store('database')));

    $this->postJson(route('password.email'), $payload)->assertTooManyRequests();
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});
