@extends('auth.authbase' , ['title'=>'Sign In'])
@section('form')
    <div class="form-panel">
        @include( 'snip.input' , [ 'id'=>'username' ,  'autocomplete'=>'username' ,'placeholder'=>'Account Name' ,  'value'=>$_POST['username'] ?? 'wooster' , 'label'=>'Account Name(i)','info'=>'Usually this will be your email address'])
    </div>
    <div class="form-panel">
        @include( 'snip.password' , [ 'id'=>'password' , 'autocomplete'=>'current-password', 'placeholder'=>'Password', 'label'=>'Password',  'value'=>$_POST['password'] ?? 'TX1A-VBC2' ])
    </div>
    <div class="form-panel">
        <div class="flex justify-between items-start space-x-2">
            @include( 'snip.checkbox' , [ 'id'=>'remember' , 'value'=> ( $_POST['rememeber'] ?? false ) , 'label'=>'Remember me (i)' , 'info'=>'Keep logged in on this device for upto 15 days'])
            <div><a href="/auth/reset-password" class="text-sm underline">Reset password</a></div>
        </div>
    </div>
@endsection
@section( 'form-submit' )

    <div class="form-panel">

        <button type="submit" name="_login" id="_login" class="bar-button button-hover">Next</button>

    </div>

    <div class="form-panel">
        <a href="/auth/cancel" class="text-sm underline">Cancel</a>
    </div>
@endsection