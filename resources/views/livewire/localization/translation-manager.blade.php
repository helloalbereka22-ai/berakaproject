<div>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Translation Management</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Manage static text, labels, and product names across different languages.
                </p>
            </div>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="shadow sm:rounded-md sm:overflow-hidden bg-white px-4 py-5 sm:p-6 space-y-6">
                
                @if (session()->has('success'))
                    <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700">Select Language</label>
                    <select wire:model.live="selectedLanguageId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">-- Choose Language --</option>
                        @foreach($languages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->name }} ({{ strtoupper($lang->code) }})</option>
                        @endforeach
                    </select>
                    @error('selectedLanguageId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                @if($selectedLanguageId)
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Add / Update Translation</h4>
                    <form wire:submit.prevent="saveTranslation" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Group</label>
                            <select wire:model="group" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="general">General</option>
                                <option value="category">Category</option>
                                <option value="product">Product</option>
                                <option value="attribute">Attribute</option>
                            </select>
                            @error('group') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Key (Identifier)</label>
                            <input type="text" wire:model="key" placeholder="e.g. welcome_msg" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('key') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Value (Translation)</label>
                            <input type="text" wire:model="value" placeholder="e.g. مرحبا" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Existing Translations</h4>
                    @if(count($translations) > 0)
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Group</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Key</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Delete</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($translations as $t)
                                        <tr>
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ ucfirst($t->group) }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $t->key }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $t->value }}</td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <button wire:click="deleteTranslation({{ $t->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No translations defined for this language.</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
