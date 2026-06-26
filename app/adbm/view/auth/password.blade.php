@extends('auth.authbase' , ['title'=>'Enter Password'])
@section('form')

    <div class="form-panel">
        <input type="hidden" name="username" autocomplete="username" value="{{$username}}">
        <span>Signing in as: {{$username}} <span><a href="/auth/start" class="text-sm underline text-blue-800">(Change)</a></span></span>

    </div>

    <div class="form-panel">
        @component('snip.input', [
            'label'=>['password', 'password'],
            'type'=>'password',
            'id'=>'password' ,
            'autocomplete'=>'current-password' ,
            'placeholder'=>'Password' ,
            'value'=>$password ?? '' ,
            'autofocus'=>true     ])
        @endcomponent
        <div class="flex justify-between items-center h-4 mt-2">
        <div class="text-sm">
            <input class="h-3 w-3" id="_remember" name="_remember" type="checkbox">
            <label for="_remember"><?php echo \app\wd\UI::note( 'rem' ) ;  ?></label>
        </div>
        <div class="text-right text-sm">
            <a href="/auth/reset" class="underline">Reset password</a></div>
        </div>

    </div>


    <button type="submit"
            name="_login"
            id="_login"
            value="li"
            class="bar-button button-hover">Continue
    </button>

    <div>
        <a href="/" class="text-sm underline">Cancel</a>
    </div>

@endsection