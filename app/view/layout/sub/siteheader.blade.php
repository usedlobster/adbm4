<header class="sticky top-0 before:absolute before:inset-0 before:backdrop-blur-md before:bg-white/90 dark:before:bg-gray-800/90 lg:before:bg-gray-100/90 dark:lg:before:bg-gray-900/90 before:-z-10 max-lg:shadow-xs z-30">
    <div class="relative">
        <div class="px-4 sm:px-6 lg:px-8 bg-cover bg-center bg-no-repeat w-full bg-[url('/img/bkg.png')] dark:bg-[url('/img/bkg-d.png')] border-b-4">
            <div class="flex items-center justify-between h-16 lg:border-b border-gray-200 dark:border-gray-700/60">

                <!-- Header: Left side -->
                <div class="flex">
                    <!-- Hamburger button -->
                    <button
                            class="lg:hidden"
                            @click.stop="sidebarOpen = !sidebarOpen"
                            aria-controls="sidebar"
                            :aria-expanded="sidebarOpen"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <svg class="button-hover w-8 h-8 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <rect x="4" y="5"  width="16" height="2" />
                            <rect x="4" y="11" width="16" height="2" />
                            <rect x="4" y="17" width="16" height="2" />
                        </svg>
                    </button>
                    @if ( !($hide_logos ?? false) )
                    <div class="relative inset-0 flex justify-center items-center overflow-hidden space-x-2 px-2 h-16 ">
                        <img src="/img/tmp/dummy_480x96.png" alt="Logo 1" class="h-12 w-auto object-contain flex-1 min-w-24">
                        <img src="/img/tmp/dummy_480x96.png" alt="Logo 2" class="h-12 w-auto object-contain flex-1 min-w-24">
                    </div>
                    @endif
                </div>

                <!-- Header: Right side -->
                <div class="flex items-center space-x-3">
                    @if ( ($editable ?? false)  && ( $info['editor_allowed'] ?? 0 ) !== 0 )
                        @include( 'layout.sub.editor_button' )
                    @endif
                    <!-- Dark mode toggle -->
                    @include( 'layout.sub.dark_light_switch')
                    <!-- Avatar -->
                    @include( 'layout.sub.avatar')

                </div>

            </div>
        </div>
    </div>
</header>