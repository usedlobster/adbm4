@extends('auth.authbase' , ['title'=>'Authentication Code'])
@section('form')

    <div class="form-panel">

        @component('snip.input', [
             'label'=>['code', 'Six Digit Code' ],
             'id'=>'code' ,
             'autocomplete'=>'one-time-code' ,
             'placeholder'=>'Enter 6 digit code' ,
             'value'=>$code   ,
             'autofocus'=>true ,
             'maxlength'=>30 ])
        @endcomponent

        <div class="my-2">
            <button type="submit"
                    name="_login"
                    id="_login"
                    value="li"
                    class="bar-button button-hover">Continue
            </button>
        </div>
    </div>

    <div>
        <a href="/auth/signout" class="text-sm underline">Cancel</a>
    </div>

@endsection