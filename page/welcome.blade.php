@extends( 'layout.master')
@section( 'body' )
    <body class="h-full">
    <div class="min-h-full flex flex-col ">
        <main class="flex-1 flex items-center justify-cente">
            <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 w-full">
                <div class="max-w-md w-full space-y-8">
                    <div>
                        @include( 'layout.logo.head', ['title'=>'Welcome'] )
                    </div>
                    <div></div>
                    <a href="/portal" alt="Enter">
                        <a href="/portal" class="bar-button button-hover inline-block text-center">
                            @if ( $_login ?? false )
                                Enter
                            @else
                                Sign In
                            @endif
                        </a>

                    </a>
                </div>
            </div>
        </main>
    </div>
    </body>
@endsection
