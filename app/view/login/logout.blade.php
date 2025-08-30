@extends('layout.login')
@section('content')

    <div class="max-w-md w-full space-y-4">

        @include( 'component.logo-head', ['title'=>'Signed Out'] )

        <div class="text-center">
            <p>You have now been signed out</p>
        </div>
        <br />
        <a href="/"  alt="Home">
            @component( 'component.button', ['name'=>'_home'] )
                Home
            @endcomponent
        </a>

    </div>

@endsection