<?php

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function logs(): Collection
    {
        return AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->get();
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading size="2xl">{{ __('Activity logs') }}</flux:heading>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Time') }}</flux:table.column>
            <flux:table.column>{{ __('Action') }}</flux:table.column>
            <flux:table.column>{{ __('Details') }}</flux:table.column>
            <flux:table.column>{{ __('User') }}</flux:table.column>
            <flux:table.column>{{ __('IP address') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->logs as $log)
                <flux:table.row :key="$log->id">
                    <flux:table.cell>{{ $log->created_at?->format('Y-m-d H:i:s') }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ str($log->action)->replace('_', ' ')->title() }}</flux:table.cell>
                    <flux:table.cell>{{ $log->details ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <div>{{ $log->user?->name ?? __('Deleted user') }}</div>
                        @if ($log->user?->email)
                            <div class="text-sm text-zinc-500">{{ $log->user->email }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $log->ip_address ?: '-' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-8 text-center text-zinc-500">
                        {{ __('No activity has been logged yet.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
