@extends('auth.authbase' , ['title'=>'Pick Project'])
@section('form')
    @if ( isset( $list ) && count($list) > 0 )
        <div class="form-panel">
            <label for="prj" class="block text-sm font-medium text-gray-700">Available Projects</label>
            <select name="prj" id="prj" class="form-select block w-full mt-1">
                @foreach( $list as $prj )
                    <option value="{{ $prj->id }}">{{ $prj->text }}</option>
                @endforeach
            </select>
        </div>
    @endif
@endsection

@section( 'form-submit' )

    @if ( isset( $list ) && count($list) > 0 )
        <div class="form-panel">
            <button type="submit" name="_pickprj" id="_pickprj" class="bar-button button-hover">Next</button>
        </div>


    @else
        {{-- this can also happen , access token expires and not refreshed  --}}
        @component('snip.info-block',['type'=>'info'])
            Sorry, You no longer have access to any projects.
        @endcomponent
    @endif
    <div class="form-panel">
        <a href="/auth/signout" class="text-sm underline">Cancel</a>
    </div>
@endsection