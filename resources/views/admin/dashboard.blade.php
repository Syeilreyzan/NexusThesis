<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <!-- Main Container with Alpine Modal State -->
    <div class="py-12" x-data="{
        openModal: false,
        confirming: false,
        activeThesisId: null,
        studentName: '',
        currentExaminerId: ''
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        Student Thesis Overview
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Manage assigned supervisors and examiners for student theses.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Student Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Thesis Title
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    SV Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Examiner Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($theses as $thesis)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $thesis->student->name ?? 'No Student' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $thesis->student->email ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-gray-100 line-clamp-2">
                                            {{ $thesis->title }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($thesis->supervisor)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                {{ $thesis->supervisor->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                No SV
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($thesis->examiner)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                {{ $thesis->examiner->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                No Examiner
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Action Button triggers Modal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="
                                                openModal = true;
                                                confirming = false;
                                                activeThesisId = {{ $thesis->id }};
                                                studentName = '{{ addslashes($thesis->student->name ?? 'Student') }}';
                                                currentExaminerId = '{{ $thesis->examiner_id ?? '' }}';
                                            "
                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                                            Assign Examiner
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No thesis entries found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $theses->links() }}
                </div>
            </div>
        </div>

        <!-- Pop-up Modal Form -->
        <div x-show="openModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             x-transition>

            <div @click.away="openModal = false" class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 relative">

                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    Assign Examiner
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Selecting examiner for <span class="font-bold text-gray-700 dark:text-gray-300" x-text="studentName"></span>
                </p>

                <!-- Form submitting dynamically based on activeThesisId -->
                <form :action="'/admin/theses/' + activeThesisId + '/examiner'" method="POST" id="examinerForm">
                    @csrf
                    @method('PATCH')

                    <div class="mb-5">
                        <label for="examiner_id" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Select Examiner
                        </label>
                        <select name="examiner_id"
                                id="examiner_id"
                                x-model="currentExaminerId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- No Examiner (Unassign) --</option>
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}">{{ $lecturer->name }} ({{ $lecturer->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Two-Step Submit Confirmation Box -->
                    <template x-if="confirming">
                        <div class="p-3 mb-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/50 rounded-lg text-xs text-amber-800 dark:text-amber-200">
                            Are you sure you want to save this examiner assignment? Click <strong>Confirm & Submit</strong> to finalize.
                        </div>
                    </template>

                    <div class="flex justify-end gap-2">
                        <button type="button"
                                @click="openModal = false; confirming = false;"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                            Cancel
                        </button>

                        <!-- First Click: Asks for Confirmation | Second Click: Submits Form -->
                        <button type="button"
                                x-show="!confirming"
                                @click="confirming = true"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg">
                            Submit
                        </button>

                        <button type="submit"
                                x-show="confirming"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg transition">
                            Confirm & Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
