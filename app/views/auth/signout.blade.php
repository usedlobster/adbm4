@extends('auth.authbase' , ['title'=>'Signed Out'])
@section('form')
    <div class="form-panel">

        <a href="/portal" class="bar-button button-hover">Sign In</a>

    </div>
@endsection
@section( 'form-submit' )

@endsection