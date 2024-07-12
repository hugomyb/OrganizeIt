<div x-data="{
        open: false,
        selectedResult: null,
        results: @entangle('results').live,
        selectedIndex: null,
        search: @entangle('search').live,

        init() {
            this.$watch('results', (value) => {
                if (value.length === 0) {
                    this.clearSelection();
                }
            });
        },

        selectResult(index) {
            this.selectedIndex = index;
            this.selectedResult = this.results[index];
        },

        clearSelection() {
            this.selectedIndex = null;
            this.selectedResult = null;
        },

        navigateResults(event) {
            if (this.results.length < 1) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (this.selectedIndex === null || this.selectedIndex === this.results.length - 1) {
                    this.selectedIndex = 0;
                } else {
                    this.selectedIndex++;
                }
                this.selectResult(this.selectedIndex);
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (this.selectedIndex === null || this.selectedIndex === 0) {
                    this.selectedIndex = this.results.length - 1;
                } else {
                    this.selectedIndex--;
                }
                this.selectResult(this.selectedIndex);
            }

            if (event.key === 'Enter' && this.selectedIndex !== null) {
                window.location.href = this.results[this.selectedIndex].url;
            }
        }
     }"
     x-init="init"
     x-mousetrap.command-k.ctrl-k.prevent="open = true; $nextTick(() => { document.getElementById('research').focus(); });"
     @keydown.window.escape="open = false"
     @keydown="navigateResults($event)">

    <!-- Modal Background -->
    <div x-show="open" class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"
         style="z-index: 9998; display: none"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <!-- Modal Content -->
    <div x-show="open" class="fixed flex-col inset-0 flex items-center"
         @click="open = false"
         style="z-index: 9999; backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); display: none">
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
            <template x-if="search">
                <div class="my-4 mx-2 rounded-lg"
                     style="scrollbar-color: #4F46E5 #E5E7EB; overflow-y: auto; max-height: 50vh; scrollbar-width: thin">
                    <ul class="flex flex-col gap-2 rounded-lg">
                        <template x-for="(result, index) in results" :key="index">
                            <li class="rounded-lg !outline-none w-full transition ease-in-out hover:bg-blue-100 dark:hover:!bg-blue-100 dark:hover:!text-black"
                                :class="{'bg-blue-100 dark:!text-black': selectedIndex === index, 'bg-gray-100 dark:bg-gray-800': selectedIndex !== index}" tabindex="-1" x-bind:id="'result-' + index">
                                <a :href="result.url"
                                   class="rounded-lg w-full">
                                    <div class="flex justify-between items-center px-4 py-4 sm:px-6 w-full rounded-lg">
                                        <div class="flex gap-2">
                                            <div class="flex-shrink-0 w-5 h-5 rounded-full"
                                                 :style="'background-color:' + result.color + '; margin-right: 15px'"></div>
                                            <p class="text-sm font-medium" x-text="result.name"></p>
                                        </div>
                                        <x-filament::icon
                                            class="w-5 h-5"
                                            color="gray"
                                            icon="heroicon-m-chevron-right"
                                            label="New label"
                                        />
                                    </div>
                                </a>
                            </li>
                        </template>
                        <template x-if="results.length < 1">
                            <li>
                                <div class="flex items-center px-4 py-4 sm:px-6">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('general.no_results') }}</p>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>
</div>
