<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'John Doe',
        'email' => 'test@example.com',
    ]);

    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->hasRole('student'))->toBeTrue();
});

test('registration rejects invalid email and unconfirmed password', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);
});
