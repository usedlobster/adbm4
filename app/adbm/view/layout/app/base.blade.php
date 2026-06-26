@extends( 'layout.master' )
@section( 'body' )
    <body hidden x-data="adbmPage({
            editAllow: {{ ( $page?->edit?->allow ?? 0 ) }} ,
            editMode: {{ ( $page?->edit?->mode ?? 0 ) }}
        })"
          class="no-js font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400"
          :class="{ 'sidebar-expanded': sidebarExpanded }">
    <div class="flex h-[100dvh] overflow-hidden">
        @include( 'layout.app.sidebar' )
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

            <!-- Site header -->
            @include( 'layout.app.header')
            <main class="grow dark:bg-black dark:text-white">
                <div class="px-4 py-2 full max-w-384 mx-auto min-h-full">
                    @yield( 'main-content')
                </div>
            </main>
            <footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-2 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-gray-900 dark:text-slate-400">
                &copy; {!! date('Y') !!} ADBM. All rights reserved.
            </footer>

        </div>

    </div>

    </body>
@endsection
@section( 'posthead' )
    <script src="/js/app.min.js"></script>
    @if ( (($page?->edit?->allow ?? 0 ) & 3 ) !== 0 )
        <script src="/js/vendor/hugerte/hugerte.min.js"></script>
        <script src="/js/wd/editor.min.js?q={{_BUILD}}"></script>
    @endif
@endsection

