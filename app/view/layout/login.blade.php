@extends('layout.master')
@section('body')
    <body class="h-full">
        <div class="min-h-full flex flex-col px-2 sm:px-0">
            <main class="flex-1 flex items-center justify-center">
                @yield('content')
            </main>
            @include('layout.sub.footer')
        </div>
    </body>
@endsection