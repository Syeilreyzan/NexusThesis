<?php

use App\Models\User;
test('email verification page is not available', function () {
    $this->get('/email/verify')->assertNotFound();
});

test('users without a verified email can access the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
