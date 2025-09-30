@extends('layouts.auth')
@section('content')

    @include( 'snip.logo-head', ['title'=>'Your password has been reset'] )
    @include( 'snip.error-block' , ['error'=> $error ?? false ])


    <a href="/portal" class="mt-6">
        <div class="mt-6">
        @component('comp.button',['name'=>'_sendcode'])
            Login
        @endcomponent
        </div>
    </a>


@endsection