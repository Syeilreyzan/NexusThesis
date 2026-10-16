<?php

namespace Tests\Feature;

use App\Models\Thesis;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
