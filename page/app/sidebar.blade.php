<div class="min-w-fit text-black dark:text-white  border-r-2">
    <!-- Sidebar backdrop  -->
    <div
            class="fixed inset-0 bg-gray-800/30 dark:bg-gray-200/30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
            :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
            aria-hidden="true"
            x-cloak
    ></div>

    <!-- Sidebar -->
    <div
            id="sidebar"
            class="flex flex-col absolute z-40 left-0 top-0
            lg:static lg:left-auto lg:top-auto lg:translate-x-0
            h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar
            w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:w-64! shrink-0
            bg-white dark:bg-black shadow-xs  p-0 transition-all duration-200 ease-in-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'"
            @click.outside="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false"
            x-cloak="lg"
    >

        <!-- Sidebar header -->
        <div class="flex justify-between mb-10 pl-3 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar"
                    :aria-expanded="sidebarOpen">
                <span class="sr-only">Close Sidebar</span>
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z"/>
                </svg>
            </button>

            <!-- Sidebar Logo -->
            <a class="block" href="/">
                <img src="/img/logo.svg" alt="ADBM Logo" aria-hidden="true" class="button-hover w-14 h-14 m-1 p-1"/>
            </a>
        </div>

        <!-- Links -->
        <div class="space-y-6">
            @foreach( \app\AppMaster::getMenu() ?? [] as $group )
                <div>
                    <!-- Group Title -->
                    <h3 class="sidebar-menu-head pl-3">
                        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block ellipsis truncate">{{ $group['title'] ?? ''}}</span>
                    </h3>
                    <!-- Group Menu Items -->
                    @if ( count( $group['items']) > 0 )
                        <ul class="mt-2">
                            @foreach( $group['items'] as $item )
                                <li class="pl-4 pr-3 py-2  mb-0.5 last:mb-0" x-data="{ open: false }">
                                    <!-- Menu Item -->
                                    @include( 'layout.snip.menu.item' , ['group'=>$group , 'item'=> $item] )
                                    <div class="hidden sidebar-expanded:block">
                                        @if ( count( $item['items'] ?? [] ) > 0 )
                                            <ul class="pl-8 mt-1" :class="open ? 'block!' : 'hidden'">
                                                @foreach( $item['items'] ?? [] as $sub )
                                                    <li class="mb-1 pr-1 last:mb-0">
                                                        @include( 'layout.snip.menu.subitem',[ 'group'=>$group , 'item'=>$item , 'sub'=>$sub] )
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

        </div>

        <!-- Expand / collapse button -->
        <div class="pt-3 hidden lg:inline-flex  justify-end mt-auto">
            <div class="w-12 pl-4 pr-3 py-2">
                <button class="text-gray-400  transition-colors"
                        @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="button-hover shrink-0 fill-current text-gray-400 dark:text-gray-500 dark:text-gray-500  sidebar-expanded:rotate-180" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                        <path d="M12,4,L20,12L12,20L10,18.5L16,13H2V11H16L10.5,5.5L12,4M20,12V22H22V2H20V12Z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
