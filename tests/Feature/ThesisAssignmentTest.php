<?php

use App\Models\Thesis;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admins can assign a lecturer as thesis examiner', function () {
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

    $this->assertDatabaseHas('theses', [
        'id' => $thesis->id,
        'examiner_id' => $examiner->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'examiner_assigned',
    ]);
});

test('admins cannot assign the thesis supervisor as examiner', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $student = User::factory()->create()->assignRole('student');
    $supervisor = User::factory()->create()->assignRole('lecturer');
    $thesis = Thesis::factory()->create([
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.theses-table')
        ->set('selectedThesisId', $thesis->id)
        ->set('selectedExaminerId', $supervisor->id)
        ->call('assignExaminer')
        ->assertHasErrors(['selectedExaminerId']);

    $this->assertDatabaseHas('theses', [
        'id' => $thesis->id,
        'examiner_id' => null,
    ]);
});
