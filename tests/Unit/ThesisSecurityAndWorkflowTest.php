<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Thesis;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ThesisSecurityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Automatically seed the essential roles and permissions for every test
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @test */
    public function it_rejects_unauthorized_access_to_examiner_grading()
    {
        // Create a regular student user who shouldn't have examiner privileges
        $student = User::factory()->create();
        $student->assignRole('student');

        $thesis = Thesis::factory()->create();

        // Attempting to act as a student and test grading actions or restricted components
        $this->actingAs($student);

        // If trying to access a restricted controller endpoint or livewire action unauthorized:
        $response = $this->get('/examiner/theses'); // Adjust route to your actual examiner route

        // Should be forbidden (403) or redirected depending on middleware
        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_uploaded_thesis_files_safely()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student);

        // Attempt to upload a malicious/unsupported file type (e.g., .exe or .php disguised/malicious)
        $maliciousFile = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

        // Test Livewire component validation for thesis creation/upload
        Livewire::test('theses.student-thesis-manager') // Replace with your actual Livewire component path name
            ->set('title', 'Test Thesis Title')
            ->set('abstract', 'This is a valid abstract for testing purposes.')
            ->set('thesisFile', $maliciousFile)
            ->call('saveThesis')
            ->assertHasErrors(['thesisFile']); // Must fail validation due to mimes rule (pdf, doc, docx)
    }

    /** @test */
    public function it_handles_malicious_or_out_of_range_grading_inputs_safely()
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('lecturer');

        $thesis = Thesis::factory()->create([
            'examiner_id' => $examiner->id,
            'status' => 'pending_approval'
        ]);

        $this->actingAs($examiner);

        // Attempting to submit an invalid/malicious score (e.g., string injection or out-of-range number like 999)
        Livewire::test('examiner.thesis-grading-manager') // Replace with your actual examiner component class/alias
            ->set('selectedThesisId', $thesis->id)
            ->set('gradeScore', 999) // Out of max:100 rule
            ->call('submitGrade')
            ->assertHasErrors(['gradeScore']); // Must catch and reject the out-of-bounds score
    }

    /** @test */
    public function it_successfully_records_an_audit_log_upon_critical_actions()
    {
        $examiner = User::factory()->create();
        $examiner->assignRole('lecturer');

        $thesis = Thesis::factory()->create([
            'examiner_id' => $examiner->id,
        ]);

        $this->actingAs($examiner);

        // Simulate a valid grade submission
        Livewire::test('examiner.thesis-grading-manager')
            ->set('selectedThesisId', $thesis->id)
            ->set('gradeScore', 85)
            ->set('gradeStatus', 'A (Excellent)')
            ->call('submitGrade');

        // Verify that the action was correctly recorded in the audit logs table
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $examiner->id,
            'action' => 'grade_submitted',
        ]);

        $log = AuditLog::where('action', 'grade_submitted')->latest()->first();
        $this->assertStringContainsString("Examiner {$examiner->name}", $log->details);
        $this->assertStringContainsString("ID: {$thesis->id}", $log->details);
    }
}
