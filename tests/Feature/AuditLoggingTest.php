<?php

use App\Models\AuditLog;
use App\Models\Thesis;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('examiner assignment writes a complete audit log entry', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $student = User::factory()->create()->assignRole('student');
    $supervisor = User::factory()->create()->assignRole('lecturer');
    $examiner = User::factory()->create()->assignRole('lecturer');
    $thesis = Thesis::factory()->create([
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.theses-table')
        ->set('selectedThesisId', $thesis->id)
        ->set('selectedExaminerId', $examiner->id)
        ->call('assignExaminer')
        ->assertHasNoErrors();

    $auditLog = AuditLog::query()
        ->where('action', 'examiner_assigned')
        ->sole();

    expect($auditLog->user_id)->toBe($admin->id)
        ->and($auditLog->details)->toBe(
            "Admin {$admin->name} assigned {$examiner->name} (ID: {$examiner->id}) as examiner for thesis '{$thesis->title}' (ID: {$thesis->id})."
        )
        ->and($auditLog->ip_address)->toBe('127.0.0.1');
});

test('admins can view activity logs', function () {
    $admin = User::factory()->create()->assignRole('admin');

    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'examiner_assigned',
        'details' => 'Examiner assigned to thesis.',
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.logs'))
        ->assertOk()
        ->assertSee('Activity logs')
        ->assertSee('Examiner Assigned')
        ->assertSee('Examiner assigned to thesis.')
        ->assertSee($admin->name)
        ->assertSee('127.0.0.1');
});

test('non-admin users cannot view activity logs', function () {
    $student = User::factory()->create()->assignRole('student');

    $this->actingAs($student)
        ->get(route('admin.logs'))
        ->assertForbidden();
});
