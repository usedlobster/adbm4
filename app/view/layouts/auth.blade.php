@extends('layouts.master')
@section('body')
    <body class="h-full">
    <div class="min-h-full flex flex-col px-2 sm:px-0">
        <main class="flex-1 flex items-center justify-center">
            <div class="max-w-md w-full space-y-4">
                @yield('content')
            </div>
        </main>
        @include('layouts.footer')
    </div>
    </body>
@endsection