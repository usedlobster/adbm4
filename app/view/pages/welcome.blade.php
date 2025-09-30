@extends( 'layouts.master')
@section( 'body' )
    <body class="h-full">
        <div class="min-h-full flex flex-col ">
            <main class="flex-1 flex items-center justify-cente">
                <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 w-full">
                    <div class="max-w-md w-full space-y-8">
                        @include( 'snip.logo-head', ['title'=>'Welcome'] )
                        <div>
                        </div>
                        <a href="/portal" alt="Enter">
                            <button type="submit" class="group bar-button">Enter</button>
                        </a>
                    </div>
                </div>
            </main>
            @include( 'layouts.footer' )
        </div>
    </body>
@endsection
