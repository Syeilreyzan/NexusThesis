<?php

use Livewire\Component;

new class extends Component
{
    #[Session]
    public string $activeTab = 'supervisor';
};
?>

<div>
    <div class="flex space-x-4 border-b border-gray-200 mb-6">
        <button wire:click="$set('activeTab', 'supervisor')"
                class="pb-2 px-1 {{ $activeTab === 'supervisor' ? 'border-b-2 border-blue-500 text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
            Assigned Supervisor
        </button>

        <button wire:click="$set('activeTab', 'examiner')"
                class="pb-2 px-1 {{ $activeTab === 'examiner' ? 'border-b-2 border-blue-500 text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
            Assigned Examiner
        </button>
    </div>

    <!-- Tab Content Panels -->
    <div>
        @if ($activeTab === 'supervisor')
            <livewire:lecturer.supervisor-table />

        @elseif ($activeTab === 'examiner')
            <livewire:lecturer.examiner-table />
        @endif
    </div>
</div>
