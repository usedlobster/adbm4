@extends('layouts.auth')
@section('content')

    @include( 'snip.logo-head', ['title'=>'You have signed out'] )

    <div class="mt-8 space-y-6" action="#" method="POST">
        <div>
        <a href="/" alt="Enter">
            <button type="submit" class="group bar-button">Home</button>
        </a>

        </div>
        <div>
            <span>All other active logins will expire in 20 minutes</span>
        </div>
    </div>





@endsection