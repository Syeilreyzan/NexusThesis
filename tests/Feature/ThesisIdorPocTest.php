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

/**
 * PoC: `selectedThesisId` is a public, unprotected Livewire property on
 * student.thesis-table. The `selectedThesis` computed property loads the
 * thesis with Thesis::find($this->selectedThesisId) and never checks that
 * the authenticated student actually owns it.
 *
 * A normal user can never reach this through the UI, because the component
 * only ever sets selectedThesisId to theses it already fetched for the
 * logged-in student. But Livewire exposes public properties directly to
 * the client: the browser can POST any value for selectedThesisId on the
 * component's next request (e.g. by editing the wire:snapshot/updates
 * payload, or via browser devtools), and the server will accept it.
 *
 * This test skips the browser and sets the property directly through the
 * Livewire testing API, which exercises exactly the server-side code path
 * an attacker-controlled request would hit.
 */
test('a student can read another student\'s thesis via selectedThesisId tampering', function () {
    $victim = User::factory()->create()->assignRole('student');
    $attacker = User::factory()->create()->assignRole('student');
    $supervisor = User::factory()->create()->assignRole('lecturer');
    $examiner = User::factory()->create()->assignRole('lecturer');

    $victimThesis = Thesis::factory()->create([
        'student_id' => $victim->id,
        'supervisor_id' => $supervisor->id,
        'examiner_id' => $examiner->id,
        'title' => 'Victim Confidential Research Title',
        'abstract' => 'Victim confidential abstract content.',
    ]);

    // The attacker never requests or is handed this thesis anywhere in the
    // normal flow. The attacker's own `theses` computed property is scoped
    // to `student_id = auth()->id()` and would never return it.
    Livewire::actingAs($attacker)
        ->test('student.thesis-table')
        ->set('selectedThesisId', $victimThesis->id)   // <-- attacker-controlled
        ->assertSet('selectedThesis.id', $victimThesis->id)
        ->assertSee('Victim Confidential Research Title')
        ->assertSee($supervisor->name)
        ->assertSee($examiner->name);
});

/**
 * Companion test: confirms the UI-driven path (fetching via the owned
 * `theses` collection) correctly stays scoped, so the only way to reach
 * another student's thesis is by tampering with selectedThesisId directly,
 * as above.
 */
test('the theses listing never includes another student\'s thesis', function () {
    $victim = User::factory()->create()->assignRole('student');
    $attacker = User::factory()->create()->assignRole('student');
    $supervisor = User::factory()->create()->assignRole('lecturer');

    $victimThesis = Thesis::factory()->create([
        'student_id' => $victim->id,
        'supervisor_id' => $supervisor->id,
    ]);

    Livewire::actingAs($attacker)
        ->test('student.thesis-table')
        ->assertDontSee($victimThesis->title);
});
