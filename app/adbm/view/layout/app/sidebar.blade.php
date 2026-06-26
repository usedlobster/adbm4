<div class="min-w-fit text-black dark:text-white border-r-2 ">
    <!-- Backdrop , when menu open -->
    <div
            class="fixed inset-0 bg-yellow-600/30 dark:bg-blue-600/30 z-50 lg:hidden lg:z-auto transition-opacity duration-200"
            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
            aria-hidden="true"
            x-cloak>
    </div>

    <!-- Sidebar -->
    <div id="sidebar"
            class="flex flex-col absolute z-50 left-0 top-0
            lg:static lg:left-auto lg:top-auto lg:translate-x-0
            h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar
            w-64 lg:w-16 lg:sidebar-expanded:!w-64 2xl:w-64! shrink-0
            bg-white dark:bg-black shadow-xs  p-0 transition-all duration-300 ease-in-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'"
            @click.outside="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false"
            x-cloak>

        <div class="flex items-center justify-between h-12 p-2 border-b shadow-lg">
            <!-- close sidebar button left arrow -->
            <button class="lg:hidden" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
            <span class="sr-only">Close Sidebar</span>
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 19l1.5-1.5L8 13H20v-2H8l4-4-1.5-1.5L4 12z"/>
            </svg>
            </button>
            <span class="lg:block"></span>

            <a class="pl-0.5 button-hover"  href="/">
                <img src="/img/logo.svg" alt="ADBM Logo" aria-hidden="true" class="button-hover ///w-10 h-10"/>
            </a>

        </div>
        <!-- Links -->

        <div class="pl-2 space-y-4 mt-4">
{{--            <h1 class="text-lg underline italic">--}}
{{--                <span class="2xl:block ellipsis truncate"></span>--}}
{{--            </h1>--}}
            <?php \app\wd\Menu::showMenu( ) ; ?>
        </div>

        <!-- Expand / collapse button -->
        <div class="hidden lg:inline-flex justify-end mt-auto">
            <div class="w-12 ">
                <button class="text-gray-400  transition-colors"
                        @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="button-hover shrink-0 fill-current text-gray-400 dark:text-gray-500 dark:text-gray-500  sidebar-expanded:rotate-180"
                         xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                        <path d="M12,4,L20,12L12,20L10,18.5L16,13H2V11H16L10.5,5.5L12,4M20,12V22H22V2H20V12Z"></path>
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>

