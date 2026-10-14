<h1>Theses</h1>

@can('create', App\Models\Thesis::class)
    <a href="{{ route('theses.create') }}">
        Submit a thesis
    </a>
@endcan

@forelse ($theses as $thesis)
    <div>
        <h2>{{ $thesis->title }}</h2>

        <a href="{{ route('theses.show', $thesis) }}">
            View thesis
        </a>
    </div>
@empty
    <p>No theses available.</p>
@endforelse
