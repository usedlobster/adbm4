@extends('auth.authbase' , ['title'=>'Request Reset'])
@section('form')

    @if ( empty( $errormsg ) )
    @component('snip.info-block')
        <div>
            Enter your login account name belows, to enter a current reset code , or request a new one.
        </div>
    @endcomponent
    @endif

    <div class="form-panel">
        @include( 'snip.input' , [ 'id'=>'username' ,  'placeholder'=>'Account Name' ,  'value'=>$_POST['username'] ?? '' , 'label'=>'Account Name(i)','info'=>'Usually this will be your email address'])
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