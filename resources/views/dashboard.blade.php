<x-layouts::app :title="__(
    auth()->user()->hasRole('admin')
        ? 'Dashboard Admin'
        : (auth()->user()->hasRole('lecturer')
            ? 'Dashboard Lecturer'
            : 'Dashboard Student'),
)">
    <livewire:pages::teams.pending-invitations-modal />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div> --}}
        @role('admin')
            <livewire:student-table />
        @endrole
        @role('lecturer')
            <livewire:student-table />
        @endrole
        @role('student')
            <livewire:student-table />
        @endrole

        @role('admin')
            <livewire:user-table />
        @endrole

    </div>
</x-layouts::app>
