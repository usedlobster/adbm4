@extends('auth.authbase' , ['title'=>'Request Reset'])
@section('form')


    @component('snip.info-block',['noclose'=>true])
        <div>
           To get sent a reset code, or enter one received recently , enter your account name below.
        </div>
    @endcomponent

    <div class="form-panel">
        @include( 'snip.input' , [ 'id'=>'username' ,  'placeholder'=>'Account Name' ,  'value'=>$_POST['username'] ?? '' , 'label'=>'Account Name(i)',
                'info'=>'HINT:Usually this is your email address, but check previous email correspondence'])
    </div>

@endsection
@section( 'form-submit' )

    <div class="form-panel">

        <button type="submit" name="_sendcode" id="_sendcode" class="bar-button button-hover">Next</button>

    </div>

    <div class="form-panel">
        <a href="/auth/cancel-reset" class="text-sm underline">Cancel Reset</a>
    </div>
@endsection