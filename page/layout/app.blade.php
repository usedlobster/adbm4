@extends( 'layout.master' )
@section( 'body' )
    <body class="adbm-page-body" x-data="adbmPage" :class="{ 'sidebar-expanded': sidebarExpanded }">
    <div class="flex h-[100dvh] overflow-hidden">

        @include( 'app.sidebar'  )
        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">

            <!-- Site header -->
            @include( 'app.siteheader')
            <main class="grow">
                <div class="px-4 py-2 full max-w-384 mx-auto min-h-full">
                    @yield( 'main-content')
                </div>
            </main>

        </div>

    </div>

    </body>
@endsection