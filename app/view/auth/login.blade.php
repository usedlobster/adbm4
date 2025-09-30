@extends('layouts.auth')
@section('content')

        @include( 'snip.logo-head', ['title'=>'Sign in to your account'] )
        @include( 'snip.error-block' , ['error'=> $error ?? false ])

        <form class="mt-8 space-y-6" action="#" method="POST">
            @csrf
            <div class="rounded-md shadow-sm space-y-4">

                @include( 'snip.email-input'    , ['id'=>'email'    ,  'value'=>$_POST['email'] ?? 'wooster@alandaleuk.com'  ])
                @include( 'snip.password-input' , ['id'=>'password' ,  'value'=>$_POST['password'] ?? '7enTgPNsBksheHATNnrj' ])

            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm flex-grow-1">
                    <label for="remember_me" class="ml-2 block text-sm text-indigo-600 hover:text-black">

                        <input id="remember_me"
                               name="remember_me"
                               {!! (($_SESSION['_remember_me_'] ?? false ) ? 'checked ' : ' ') !!}
                               type="checkbox"
                               class="rounded text-indigo-600 focus:ring-indigo-500">
                        Keep signed in
                    </label>
                </div>
                <div class="text-sm flex-shrink-0">
                    <a href="/auth/reset-password" class="font-medium text-indigo-600 hover:text-black">
                        Reset password
                    </a>
                </div>
            </div>

            <div>
                @component('comp.button',['name'=>'_login'])
                    Sign In
                @endcomponent
            </div>

            <div>
                <a href="/" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
            </div>

        </form>



@endsection