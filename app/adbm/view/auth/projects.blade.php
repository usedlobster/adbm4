@extends('auth.authbase' , ['title'=>'Select Project'])

@section('form')
    <div class="form-panel">
        @component('snip.select', [
               'label'=>['project', 'Select your project'],
               'id'=>'project',
               'autofocus'=>true,
               'list' => $list
           ])
        @endcomponent


    </div>

    <button type="submit"
            name="_pick"
            id="_pick"
            value="pp"
            class="bar-button button-hover">Continue
    </button>

    <div>
        <a href="/" class="text-sm underline">Cancel</a>
    </div>
@endsection


