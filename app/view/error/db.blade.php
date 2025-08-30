@extends( 'layout.master')
@section( 'body' )


    <body class="h-full">

    <div class="min-h-full flex flex-col">
        <main class="flex-1 flex items-center justify-center">
            <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-md w-full space-y-8">
                    <div>
                        <div class="max-w-xs mx-auto mb-2 flex justify-center">
                            <a href="/" aria-label="Backto portal">
                                <img class="w-24 h-24" src="/img/logo.svg" alt="ADBM Logo"/>
                            </a>
                        </div>
                        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Sorry, we are experiencing technical difficulties.
                            <?php session_destroy() ; ?>
                        </h2>
                    </div>
                    <h3 class="text-center">Please try again later</h3>
                    <a href="/portal" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" alt="Start Page">
                        <button type="submit">
                           Try Again
                        </button>
                    </a>
                </div>
            </div>
        </main>

        @include( 'layout.sub.footer' )
    </div>
    </body>

@endsection
