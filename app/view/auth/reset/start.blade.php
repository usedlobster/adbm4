@extends('layouts.auth')
@section( 'meta-head' )
    <meta name="description" content="Request a password reset">
@endsection
@section('content')

    @include( 'snip.logo-head', ['title'=>'Reset Password'] )
    @include( 'snip.error-block' , ['error'=> $error ?? false ])
    @include( 'snip.info-block' , [ 'info'=> 'Enter the email address that you usually use to sign in with to continue. We will then ask for a security code, so you can reset your password.
If you have not received a code recently and your account is still active, we will send a new code to your email address shortly.'] )



    <form class="mt-8 space-y-6" action="#" method="POST">
        @csrf
        <div class="rounded-md shadow-sm space-y-4">

            @include( 'snip.email-input' , ['id'=>'email' , 'label'=>'Email Address' , 'value'=>$_POST['email'] ?? '' ])

        </div>

        <div>
            @component('comp.button',['name'=>'_sendcode'])
                Enter or Send Code
            @endcomponent
        </div>

        <div>
            <a href="/auth/cancel" class="text-sm text-indigo-600 hover:text-indigo-500">Cancel</a>
        </div>

    </form>



@endsection