<?php

namespace Tests\Feature;

use App\Models\Thesis;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class ThesisAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_student_cannot_view_another_students_thesis(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('student');

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('lecturer');

        $thesis = Thesis::factory()->create([
            'student_id' => $owner->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->actingAs($otherStudent)
            ->get(route('theses.show', $thesis));

        $response->assertForbidden();
    }

    public function test_student_cannot_tamper_with_selected_thesis_id(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('student');

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $thesis = Thesis::factory()->create([
            'student_id' => $owner->id,
        ]);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($otherStudent)
            ->test('student.thesis-table')
            ->set('selectedThesisId', $thesis->id);
    }
}
