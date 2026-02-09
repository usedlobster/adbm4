@extends( 'master' )
@section( 'body' )

    <body class="h-full">
    <div class="min-h-full flex flex-col px-2 sm:px-0">
        <main class="flex-1 flex items-center justify-center">
            <div class="max-w-md w-full space-y-4">
                @include( 'layout.logo.head' , ['title'=>'Welcome'] )
                <div></div>
                <a href="/portal" alt="Enter Portal">
                    <a href="/portal" class="bar-button button-hover inline-block text-center">
                        Enter
                    </a>
                </a>
            </div>
        </main>
    </div>
    </body>
@endsection