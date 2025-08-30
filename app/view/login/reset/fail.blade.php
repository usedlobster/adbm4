@extends('layout.login')
@section('content')

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">

        <div class="max-w-md w-full space-y-8">

            @include( 'component.logo-head' , ['title'=>'Sorry!'])
            @include( 'component.error-block' , ['error'=> 'Unable to reset your password' ])

            <div class="mt-8 space-y-6" >
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        We could not reset your password.
                    </div>

                </div>



                <div>
                    <a href="/portal" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Home
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection