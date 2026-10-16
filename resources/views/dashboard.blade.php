<x-layouts::app :title="__(
    auth()->user()->hasRole('admin')
        ? 'Dashboard Admin'
        : (auth()->user()->hasRole('lecturer')
            ? 'Dashboard Lecturer'
            : 'Dashboard Student'),
)">
    <flux:heading size="2xl" class="pb-4">
        {{ str(Auth::user()->getRoleNames()->join(', '))->title() }} Dashboard
    </flux:heading>

    <div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
        @role('admin')
            <livewire:admin.index />
        @endrole

        @role('lecturer')
            <livewire:lecturer.index />
        @endrole

        @role('student')
            <livewire:student.index />
        @endrole

    </div>
</x-layouts::app>
