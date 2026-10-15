<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto space-y-8">
        <h1 class="text-2xl font-bold text-gray-800">Lecturer Dashboard</h1>

        <!-- TABLE 1: Assigned as Supervisor (SV) -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">
                Type Assigned: SV
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thesis Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($svTheses as $thesis)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $thesis->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $thesis->student->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $thesis->student->email ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ ucfirst($thesis->status) }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700">Action</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No theses assigned as Supervisor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABLE 2: Assigned as Examiner -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">
                Type Assigned: Examiner
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thesis Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Access Expiry</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($examinerTheses as $thesis)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $thesis->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $thesis->student->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $thesis->student->email ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $thesis->examiner_access_expires_at ? $thesis->examiner_access_expires_at->format('Y-m-d') : 'No Expiry' }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700">Action</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No theses assigned as Examiner.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
