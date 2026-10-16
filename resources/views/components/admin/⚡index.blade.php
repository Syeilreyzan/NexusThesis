<?php

use Livewire\Component;
use Livewire\Attributes\Session;

new class extends Component
{
    #[Session]
    public string $activeTab = 'theses';
};
?>

<div>
    <div class="flex space-x-4 border-b border-gray-200 mb-6">
        <button wire:click="$set('activeTab', 'theses')"
                class="pb-2 px-1 {{ $activeTab === 'theses' ? 'border-b-2 border-blue-500 text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
            List of Theses
        </button>

        <button wire:click="$set('activeTab', 'lecturer')"
                class="pb-2 px-1 {{ $activeTab === 'lecturer' ? 'border-b-2 border-blue-500 text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
            List of Lecturer
        </button>

        <button wire:click="$set('activeTab', 'users')"
                class="pb-2 px-1 {{ $activeTab === 'users' ? 'border-b-2 border-blue-500 text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-800' }}">
            List of Users
        </button>
    </div>

    <!-- Tab Content Panels -->
    <div>
        @if ($activeTab === 'theses')
            <livewire:admin.theses-table />
        @elseif ($activeTab === 'lecturer')
            <livewire:admin.lecturer-table />
        @elseif ($activeTab === 'users')
            <livewire:user-table />
        @endif
    </div>
</div>
