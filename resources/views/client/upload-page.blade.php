<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            @if($user->companyUsers->count() > 1)
                <div class="mb-6">
                    <label for="company_user_id" class="block text-sm font-medium text-gray-700">{{ __('messages.select_recipient') }}</label>
                    <select id="company_user_id" name="company_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach($user->companyUsers as $companyUser)
                            <option value="{{ $companyUser->id }}" @if($companyUser->pivot->is_primary) selected @endif>
                                {{ $companyUser->name }} ({{ $companyUser->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-gray-500">{{ __('messages.select_recipient_help') }}</p>
                </div>
            @endif

            <!-- ... rest of the upload form ... -->
        </div>
    </div>
</div>
