<div class="relative">
    @if ( isset($label) )
            <?php \app\wd\UI::label( ($label[0] ?? '') , ($label[1] ?? '')   , ($label[2]  ?? '') ) ; ?>
    @endif

    <div class="relative flex items-center space-x-2">
        <select id="{{ $id }}"
               name="{{ $id }}"
               class="wd-select"
               @if(isset($disabled) && $disabled ) disabled @endif
               @if(isset($model)) x-model="{{ $model }}" @endif
               @if(isset($autocomplete)) autocomplete="{{ $autocomplete ?? '' }}" @endif
               @if(isset($autofocus) && $autofocus) autofocus @endif
               @if(isset($event1)) {!! $event1 !!}} @endif>
            @foreach( $list as $opt )
                <option value="{{$opt?->id}}">{{$opt?->name ?? '?'}}</option>
            @endforeach


        </select>



    </div>
</div>