<?php

use App\Models\Thesis;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Computed]
    public function theses(): LengthAwarePaginator
    {
        return Thesis::query()
            ->select(['id', 'title', 'status', 'created_at'])
            ->latest()
            ->paginate(10);
    }
};
?>

<div>
    <flux:heading size="lg" class="mb-4">Student Theses</flux:heading>

    <flux:table :paginate="$this->theses">
        <flux:table.columns>
            <flux:table.column>Thesis Title</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->theses as $thesis)
                <flux:table.row :key="$thesis->id">
                    <flux:table.cell variant="strong">{{ $thesis->title }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm">{{ str($thesis->status)->replace('_', ' ')->title() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $thesis->created_at->format('M j, Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="text-center text-zinc-500">
                        No thesis records yet.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
