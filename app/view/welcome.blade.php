@extends( 'layout.master')
@section( 'body' )


    <body class="h-full">

    <div class="min-h-full flex flex-col ">
        <main class="flex-1 flex items-center justify-cente">
            <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 w-full">
                <div class="max-w-md w-full space-y-8">
                    @include( 'component.logo-head', ['title'=>'Welcome'] )
                    <div>
                    </div>
                    <a href="/portal"  alt="Enter">
                        @component( 'component.button', ['name'=>'_home'] )
                            Enter
                        @endcomponent
                    </a>
                </div>
            </div>
        </main>
       @include( 'layout.sub.footer' )
    </div>
    </body>

@endsection
