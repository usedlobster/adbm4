@extends('layout.auth.base')
@section('content')

    @include( 'layout.logo.head', ['title'=>'Sign in to your account'] )

    @component('layout.snip.info-block' , ['type'=>'error' ])
        {!! $errormsg !!}
    @endcomponent

    <form class="wd-adbm-form" action="#" method="POST">
        @csrf
        <div class=" mb-4">
            <div class="bg-gray-50 p-2 space-y-2 mb-4">
                @include( 'layout.snip.input' , [ 'id'=>'username' ,  'value'=>$_POST['email'] ?? 'wooster' , 'label'=>'Account Name(i)','info'=>'Usually this is your email address'])
                @include( 'layout.snip.password' , [ 'id'=>'password' , 'label'=>'Password',  'value'=>$_POST['password'] ?? '7enTgPNsBksheHATNnrj' ])
                <div class="flex justify-between items-start space-x-2">
                    <div><input type="checkbox"><span class="ml-2 text-sm">Remember</span></div>
                    <div><a href="/auth/reset-password" class="text-sm underline wd-form-small-button">Reset password</a></div>
                </div>
            </div>
            <div class="mb-4">
                <button type="submit" name="_login" id="_login" class="bar-button button-hover">Sign in</button>
            </div>

            <div class="border-blue-500">
                <a href="/auth/cancel" class="wd-form-small-button">Cancel</a>
            </div>
        </div>
    </form>

@endsection