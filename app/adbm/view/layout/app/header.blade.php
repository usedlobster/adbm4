<header class="sticky top-0 before:absolute before:inset-0 before:backdrop-blur-md before:bg-white/90 dark:before:bg-gray-800/90 lg:before:bg-gray-100/90 dark:lg:before:bg-gray-900/90 before:-z-10 max-lg:shadow-xs -z-5">
    <div class="relative" x-cloak>
        <div class="h-12 px-4 py-0 sm:px-6 lg:px-8 bg-cover bg-center bg-no-repeat w-full bg-[url('/img/bkgnd/bkg-l.png')] dark:bg-[url('/img/bkgnd/bkg-d.png')] border-b-2">
            <div class="flex gap-x-2 items-center justify-between h-12 ">
                {{-- Header: Left Side --}}
                <div class="flex">
                    <!-- Hamburger button -->
                    <button class="lg:hidden"
                            aria-controls="sidebar"
                            :aria-expanded="sidebarOpen"
                            @click.stop="sidebarOpen = !sidebarOpen">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="button-hover w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <rect x="4" y="5" width="16" height="2"/>
                            <rect x="4" y="11" width="16" height="2"/>
                            <rect x="4" y="17" width="16" height="2"/>
                        </svg>
                    </button>


                    @foreach( $page->info?->logos ?? [] as $logo  )
                        <img src="/live/{{ $logo?->src }}" alt="{{ $logo?->alt  }}" class="h-12 w-auto object-contain flex-1 min-w-24">
                   @endforeach


                </div>
                {{-- Header: Right side --}}
                <div class="flex space-x-2">
                    {{-- dark/light button --}}
                    <button @click.stop="toggleEdit()">
                        <span class="sr-only">Toggle Edit</span>
                        <div class="dark:hidden block text-yellow-500" @click.stop="setDark(true)">
                            <svg class="button-hover w-7 h-7 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,2L14.39,5.42C13.65,5.15 12.84,5 12,5C11.16,5 10.35,5.15 9.61,5.42L12,2M3.34,7L7.5,6.65C6.9,7.16 6.36,7.78 5.94,8.5C5.5,9.24 5.25,10 5.11,10.79L3.34,7M3.36,17L5.12,13.23C5.26,14 5.53,14.78 5.95,15.5C6.37,16.24 6.91,16.86 7.5,17.37L3.36,17M20.65,7L18.88,10.79C18.74,10 18.47,9.23 18.05,8.5C17.63,7.78 17.1,7.15 16.5,6.64L20.65,7M20.64,17L16.5,17.36C17.09,16.85 17.62,16.22 18.04,15.5C18.46,14.77 18.73,14 18.87,13.21L20.64,17M12,22L9.59,18.56C10.33,18.83 11.14,19 12,19C12.82,19 13.63,18.83 14.37,18.56L12,22Z"></path>
                            </svg>

                        </div>
                        <div class="dark:block hidden text-white" @click.stop="setDark(false)">
                            <svg class="button-hover w-7 h-7 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.75,4.09L15.22,6.03L16.13,9.09L13.5,7.28L10.87,9.09L11.78,6.03L9.25,4.09L12.44,4L13.5,1L14.56,4L17.75,4.09M21.25,11L19.61,12.25L20.2,14.23L18.5,13.06L16.8,14.23L17.39,12.25L15.75,11L17.81,10.95L18.5,9L19.19,10.95L21.25,11M18.97,15.95C19.8,15.87 20.69,17.05 20.16,17.8C19.84,18.25 19.5,18.67 19.08,19.07C15.17,23 8.84,23 4.94,19.07C1.03,15.17 1.03,8.83 4.94,4.93C5.34,4.53 5.76,4.17 6.21,3.85C6.96,3.32 8.14,4.21 8.06,5.04C7.79,7.9 8.75,10.87 10.95,13.06C13.14,15.26 16.1,16.22 18.97,15.95M17.33,17.97C14.5,17.81 11.7,16.64 9.53,14.5C7.36,12.31 6.2,9.5 6.04,6.68C3.23,9.82 3.34,14.64 6.35,17.66C9.37,20.67 14.19,20.78 17.33,17.97Z"></path>
                            </svg>

                        </div>

                    </button>

                    {{-- edit button --}}
                    <div x-show="editAllow !=0" x-cloak>
                        <button @click.stop="toggleEdit()"
                                :class="
                                 (editMode == 0 ? 'text-gray-500 dark:text-gray-300'  :
                                 (editMode == 1 ? 'text-red-500  dark:text-red-300'   :
                                 (editMode == 2 ? 'text-lime-500 dark:text-green-300' : 'hidden' )))">
                            <span class="sr-only">Toggle Edit</span>
                            <svg class="button-hover w-7 h-7 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20,7.5C21,6.5 21,6 20.75,5.5L18.5,3C18,3 17.5,3 17,3.5L15,5L19,9M3,17.25V21H6.75L18,10L14,6L3,17.25Z "></path>
                            </svg>
                        </button>
                    </div>

                    <!-- avatar button -->
                    <div class="relative inline-flex" x-data="{ open: false }">
                        <button
                                class="inline-flex justify-center items-center group"
                                aria-haspopup="true"
                                @click.prevent="open = !open"
                                :aria-expanded="open"
                        >

                            <svg class="w-7 h-7 shrink-0 ml-1 fill-current" viewBox="0 0 24 24">
                                <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"></path>
                            </svg>

                            <div class="flex items-center truncate">
                                <span class="truncate ml-2 text-sm font-medium dark:text-blue-500 group-hover:text-blue-500 dark:group-hover:text-green-500">{{ $page->info->displayname  }}</span>
                                <svg class="w-3 h-3 shrink-0 ml-1 fill-current" viewBox="0 0 12 12">
                                    <path d="M6 11.5L.5 6l1.5-1.5 4 4 4-4L11.5 6z"/>
                                </svg>
                            </div>
                        </button>
                        <!-- avatar dropdown menu -->
                        <div
                                class="sidebar-avatar-dropdown"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                                @focusout="if (!$el.contains($event.relatedTarget)) open = false"
                                x-show="open"
                                x-transition:enter="transition ease-out duration-200 transform"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-out duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                x-cloak>
                            <div class="topmenu">
                                <div class="item1">{{ $page->info->name  }}</div>
                                <div class="item2">{{ $page->info->roles }}</div>
                                <div class="item2">Level : {{ $page->info->level }}</div>
                            </div>
                            <ul>
                                <li>
                                    <a class="" href="/user/chgpwd" @click="open = false">Change Password</a>
                                </li>
                                <li>
                                    <a class="" href="/auth/signout" @click="open = false">Sign Out</a>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>


            </div>
        </div>
    </div>
</header>