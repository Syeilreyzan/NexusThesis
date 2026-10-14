<h1>Submit Thesis</h1>

<form method="POST" action="{{ route('theses.store') }}">
    @csrf

    <label for="title">Thesis title</label>
    <input id="title" name="title" value="{{ old('title') }}" required>

    @error('title')
        <p>{{ $message }}</p>
    @enderror

    <label for="abstract">Abstract</label>
    <textarea id="abstract" name="abstract">{{ old('abstract') }}</textarea>

    @error('abstract')
        <p>{{ $message }}</p>
    @enderror

    <button type="submit">Submit thesis</button>
</form>

$student = App\Models\User::where('email', 'student1@gmail.com')->first();

$lecturer = App\Models\User::where('email', 'lecturer@gmail.com')->first();

$thesis = new App\Models\Thesis([
    'title' => 'Online Thesis Management System',
    'abstract' => 'A system for managing student thesis submissions and reviews.',
]);

$thesis->student()->associate($student);

if ($lecturer) {
    $thesis->supervisor()->associate($lecturer);
}

$thesis->save();