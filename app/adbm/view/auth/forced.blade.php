@extends('auth.authbase' , ['title'=>'Signed Out'])
@section('form')
    <div class="text-center text-lg">
    <p>Your session has expired</p>
    <p>Please sign in again to continue.</p>
    </div>
    <br />

    <div class="form-panel">
        <div class="my-2">
            <a href="/auth/start" class="bar-button button-hover">Sign In</a>
        </div>
    </div>
@endsection