@extends('layout.login')
@section('content')

    <div class="max-w-md w-full space-y-4">

        @include( 'component.logo-head'   , ['title'=>'Request password reset'] )
        @include( 'component.error-block' , ['error'=> $error ])

        <form class="mt-8 space-y-6" action="#" method="POST">
            @csrf
            @component( 'component.info-block' , [] )
                <p>
                Enter your email address to reset your password.
                </p>

                <small>
                    <br/>
                If you haven't already received a password reset code, you'll receive instructions by email. It may take several minutes - please check both your inbox and spam folder before requesting again.
                </small>
            @endcomponent


            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="email-address" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email-address"
                           value="{{$email}}"
                           name="email-address"
                           type="text"
                           role="textbox"
                           aria-required="true"
                           autocomplete="email"
                           class="mt-1 block w-full rounded-md"
                           placeholder="Email address">
                </div>

            </div>

            <div>
                @component('component.button' , ['name'=>'_sendcode'])
                    Continue
                @endcomponent
            </div>

            <div>
                <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
            </div>

        </form>

    </div>

@endsection