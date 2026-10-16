<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admins can view activity logs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'examiner_assigned',
        'details' => 'Examiner assigned to thesis.',
        'ip_address' => '127.0.0.1',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.logs'));

    $response->assertOk()
        ->assertSee('Activity logs')
        ->assertSee('Examiner Assigned')
        ->assertSee('Examiner assigned to thesis.')
        ->assertSee($admin->name)
        ->assertSee('127.0.0.1');
});

test('non-admin users cannot view activity logs', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $response = $this->actingAs($student)->get(route('admin.logs'));

    $response->assertForbidden();
});
