<a class="hover:scale-105 hover:font-bold text-xs uncate transitionblock transition truncate"
   href="{!! $sub['href'] ?? ''  !!}">
    <div class="flex items-center">
        <svg class="shrink-0 fill-current w-4 h-4"
             xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 24 24">

                <path d="M10,14V10H14V14H10Z"></path>

        </svg>
        <span class="lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">{{$sub['title'] ?? '' }}</span>
    </div>
</a>
