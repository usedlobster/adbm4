@extends('layout.auth.base')
@section( 'meta-head' )
    <meta name="description" content="Request a password reset">
@endsection
@section('content')

    @include( 'layout.logo.head', ['title'=>'Reset Password'] )

    @component('layout.snip.info-block' , ['type'=>'error' ])
        {!! $errormsg !!}
    @endcomponent

    <form class="wd-adbm-form" action="#" method="POST">
        @csrf
        <div class="mb-4">

            <div class="bg-gray-50 p-2 space-y-2 mb-4">
                @include( 'layout.snip.input' , [
                    'id'=>'email' ,
                    'label'=>'Account Name(i)' ,
                    'info' => 'Please enter the account name to reset, this is usually your email address' ,
                    'value'=>$_POST['email'] ?? '' ])
            </div>


            <div class="mb-4">
                <button type="submit" name="_sendcode" id="_login" class="bar-button button-hover">Next</button>
            </div>

            <div class="border-blue-500">
                <a href="/auth/cancel" class="wd-form-small-button">Cancel</a>
            </div>


        </div>


    </form>

@endsection