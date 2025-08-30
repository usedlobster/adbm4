@extends('layout.login')
@section('content')

    <div class="max-w-md w-full space-y-4">

        @include( 'component.logo-head', ['title'=>'Sign in to your account'] )
        @include( 'component.error-block' , ['error'=> $error ])

        <form class="mt-8 space-y-6" action="#" method="POST">
            @csrf
            <div class="rounded-md shadow-sm space-y-4">

                <div>
                    <label for="email-address" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email-address"
                           value="{!!  $_COOKIE['email'] ?? 'wooster@alandaleuk.com' !!}"
                           name="email"
                           type="text"
                           role="textbox"
                           aria-required="true"
                           autocomplete="email"
                           class="mt-1 block w-full rounded-md"
                           placeholder="Email address">
                </div>

                @include( 'component.password-input' , ['id'=>'password' , 'value'=>'7enTgPNsBksheHATNnrj' ])

            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm flex-grow-1">
                    <label for="remember-me" class="ml-2 block text-sm text-indigo-600 hover:text-black">
                    <input id="remember-me" name="remember-me" type="checkbox"  class="rounded text-indigo-600 focus:ring-indigo-500">
                        Keep signed in
                    </label>
                </div>
                <div class="text-sm flex-shrink-0">
                    <a href="/reset/reset-password" class="font-medium text-indigo-600 hover:text-black">
                        Reset password
                    </a>
                </div>
            </div>

            <div>
                @component('component.button',['name'=>'_login'])
                    @include('icon.login')
                    Sign In
                @endcomponent
            </div>

            <div>
                <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
            </div>

        </form>

    </div>

@endsection