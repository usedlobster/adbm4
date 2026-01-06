@extends('layout.auth.base')
@section( 'meta-head' )
    <meta name="description" content="Enter a code to reset your password">
@endsection
@section('content')
    @include( 'layout.logo.head', ['title'=>'Enter Code'] )
    @component('layout.snip.info-block' , ['type'=>'error' ])
        {!! $errormsg !!}
    @endcomponent
    <form class="wd-adbm-form" action="#" method="POST">
        @csrf
        <div class=" mb-4" x-data="otpInput()" >

            <div class="bg-gray-50 p-2 space-y-2 mb-4">
                @include( 'layout.snip.code' , ['id'=>'check-code' , 'label'=>'Reset Code'  ])
            </div>

            <div class="mb-4">
                <button :disabled="!(/^[1-9A-Ha-h]{8}$/.test(value))" type="submit" name="check-code_submit" id="check-code_submit" class="bar-button button-hover">Next</button>
            </div>


            <div class="border-blue-500">
                <a href="/auth/cancel" class="wd-form-small-button">Cancel</a>
            </div>


        </div>
    </form>

@endsection