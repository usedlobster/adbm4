@extends( 'master')
@section( 'body' )
    <body hidden x-data="adbmPage()"
          class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400"
          :class="{ 'sidebar-expanded': sidebarExpanded }">

    <div class="flex h-[100dvh] overflow-hidden">

        <?php
        if (method_exists($ui, 'sideBar'))
            $ui->sideBar(); ?>

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Site header -->
            @include( 'layout.siteheader')
            <main class="grow bg-gray-50 dark:bg-gray-800 dark:text-white text-black font-md">
                <div class="px-4 py-2.5 full max-w-384 mx-auto min-h-full">
                    @yield( 'app' )
                </div>
            </main>
        </div>
    </div>
    <script src="/boot/auth.php"></script>
    <script>

        document.addEventListener('DOMContentLoaded', () => {
            @yield( 'app-exec' ) ;
        })
    </script>
    </body>
@endsection

@section( 'script' )

    <script src="/js/wd/adbmpage.min.js"></script>
    @yield( 'app-script' )
@endsection