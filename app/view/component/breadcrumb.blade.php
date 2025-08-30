<div class="flex px-5 py-3  dark:bg-yellow-800 bg-yellow-200" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
        @foreach( $breadcrumb ?? [] as $item )

            <li class="inline-flex items-center">
                <a href="{!! $item['href'] ?? '#' !!}" class="inline-flex items-center ">
                    {!! $item['svg']    ?? '' !!}
                    {!! $item['title']  ?? '' !!}
                </a>
            </li>
        @endforeach
    </ol>
</div>