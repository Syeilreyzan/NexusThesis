<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'lecturer', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

test('students cannot access admin or lecturer dashboards', function () {
    $student = User::factory()->create()->assignRole('student');

    $this->actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('lecturer.dashboard'))
        ->assertForbidden();
});

test('lecturers cannot access admin activity logs', function () {
    $lecturer = User::factory()->create()->assignRole('lecturer');

    $this->actingAs($lecturer)
        ->get(route('admin.logs'))
        ->assertForbidden();
});

test('admins can access the admin dashboard', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
