<div x-data="{ open: false,}"
     x-mousetrap.command-k.ctrl-k.prevent="open = true; $nextTick(() => { document.getElementById('research').focus(); });"
     @keydown.window.escape="open = false">

    <!-- Modal Background -->
    <div x-show="open" class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"
         style="z-index: 9998; display: none"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <!-- Modal Content -->
    <div x-show="open" class="fixed flex-col inset-0 flex items-center"
         @click="open = false"
         style="z-index: 9999;  backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); display: none">
        <!-- Search Bar -->
        <div class="border rounded-lg border-gray-300 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
             @click.stop
             style="width: 50%; margin-top: 80px;">
            <div class="sm:flex sm:items-start">
                <div class="text-center sm:text-left w-full">
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                        </div>
                        <input type="search" wire:model.live="search" id="research"
                               class="block ring-0 w-full p-4 text-sm bg-gray-50 dark:bg-gray-700 dark:placeholder-gray-400"
                               style="padding-left: 2.5rem; padding-right: 3.5rem; border-bottom: 1px; outline: none !important; -webkit-box-shadow: none; box-shadow: none; border: none; {{ strlen($search) < 1 ? 'border-radius: 0.5rem;' : 'border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; border-bottom: 1px solid rgba(175,175,175,0.28);' }}"
                               placeholder="{{ __('general.research_placeholder') }}" required/>
                        <kbd
                            @click="open =false"
                            style="margin: 10px; vertical-align: middle; display: flex; align-items: center"
                            class="absolute cursor-pointer inset-y-0 end-0 px-2 py-1.5 text-xs font-semibold bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-500">
                            <p>Echap</p>
                        </kbd>
                    </div>
                </div>
            </div>

            <!-- Search Results -->
            @if ($search)
                <div class="my-4 mx-2 rounded-lg"
                    style="scrollbar-color: #4F46E5 #E5E7EB; overflow-y: auto; max-height: 50vh; scrollbar-width: thin">
                    <ul class="flex flex-col gap-2 rounded-lg">
                        @forelse ($results as $result)
                            <li class="rounded-lg w-full bg-gray-200 dark:bg-gray-800">
                                <a href="{{ $result['url'] }}"
                                   class="rounded-lg w-full">
                                    <div class="flex justify-between items-center px-4 py-4 sm:px-6 hover:bg-gray-400/10 dark:hover:bg-white/5 w-full">
                                        @if($result instanceof \App\Models\Project)
                                            <div class="flex gap-2">
                                                <div class="flex-shrink-0 w-5 h-5 rounded-full"
                                                     style="background-color: {{ $result->color }}; margin-right: 15px"></div>
                                                <p class="text-sm font-medium">{{ $result['name'] }}</p>
                                            </div>
                                        @elseif($result instanceof \App\Models\Task)
                                            <!-- TODO -->
                                        @endif
                                            <x-filament::icon
                                                class="w-5 h-5"
                                                color="gray"
                                                icon="heroicon-m-chevron-right"
                                                label="New label"
                                            />
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li>
                                <div class="flex items-center px-4 py-4 sm:px-6">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('general.no_results') }}</p>
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
