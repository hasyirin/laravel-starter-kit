<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;

test('a passkey login sends the browser to the highest area the user can open', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user);
    $request = Request::create(route('passkey.login'), 'POST', server: ['HTTP_ACCEPT' => 'application/json']);

    $response = TestResponse::fromBaseResponse(resolve(PasskeyLoginResponse::class)->toResponse($request));

    $response->assertOk()->assertExactJson(['redirect' => route('admin.dashboard')]);
});
