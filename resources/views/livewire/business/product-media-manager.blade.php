<div>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Product Media</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Upload and manage images for <strong>{{ $product->name }}</strong>.
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

                <!-- Upload Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Upload Images</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative"
                         x-data="{ isUploading: false, progress: 0 }"
                         x-on:livewire-upload-start="isUploading = true"
                         x-on:livewire-upload-finish="isUploading = false"
                         x-on:livewire-upload-error="isUploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress">
                        
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload files</span>
                                    <input id="file-upload" wire:model="photos" type="file" multiple class="sr-only" accept="image/*">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div x-show="isUploading" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-md">
                            <div class="w-1/2 bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" x-bind:style="`width: ${progress}%`"></div>
                            </div>
                        </div>
                    </div>
                    @error('photos.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                @if($photos)
                    <div class="flex justify-end mt-2">
                        <button wire:click="uploadImages" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Save Uploaded Images</button>
                    </div>
                @endif

                <!-- Image Gallery -->
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Gallery</h4>
                    
                    @if($product->media->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach($product->media as $index => $media)
                                <div class="relative group rounded-lg overflow-hidden border {{ $media->is_primary ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-200' }}">
                                    <img src="{{ Storage::url($media->file_path) }}" alt="Product Image" class="h-32 w-full object-cover">
                                    
                                    @if($media->is_primary)
                                        <span class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">Primary</span>
                                    @endif

                                    <!-- Hover Actions -->
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="flex flex-col space-y-2">
                                            @if(!$media->is_primary)
                                                <button wire:click="setPrimary({{ $media->id }})" class="text-white text-xs bg-gray-800 hover:bg-gray-700 px-2 py-1 rounded">Set Primary</button>
                                            @endif
                                            
                                            <div class="flex space-x-2 justify-center">
                                                @if(!$loop->first)
                                                    <button wire:click="moveUp({{ $media->id }})" class="text-white bg-gray-800 hover:bg-gray-700 px-2 py-1 rounded">&larr;</button>
                                                @endif
                                                @if(!$loop->last)
                                                    <button wire:click="moveDown({{ $media->id }})" class="text-white bg-gray-800 hover:bg-gray-700 px-2 py-1 rounded">&rarr;</button>
                                                @endif
                                            </div>

                                            <button wire:click="deleteImage({{ $media->id }})" wire:confirm="Delete this image?" class="text-white text-xs bg-red-600 hover:bg-red-700 px-2 py-1 rounded">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No images uploaded yet.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
