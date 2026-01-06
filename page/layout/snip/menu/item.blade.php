<a class="hover:scale-105 hover:font-bold font-medium block truncate transition" href="{{ $group['headref'] ?? '#0' }}" @click.prevent="open = !open; sidebarExpanded = true">
    <!-- icon + text + dropdown -->
    <div class="flex items-center justify-between">
        <!-- icon -->
        <div class="flex items-center" title="{{$item['title'] ?? '' }}">
            <svg class="shrink-0 text-blue-500 dark:text-green-500 fill-current w-4 h-4"
                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M3,3V21H21V3"></path> <!-- large square -->
            </svg>
            <!-- text  -->
            <span class="text-sm  ml-4 opacity-0 sidebar-expanded:opacity-100 duration-200">{{$item['title'] ?? '' }}</span>
        </div>
        <!-- dropdown - if needed  -->
        @if ( count(( $item['items'] ?? [] )) > 0 )
            <div class="flex shrink-0 ml-2 mr-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200 transition">
                <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500"
                     :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                    <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z"/>
                </svg>
            </div>
        @endif
    </div>
</a>
