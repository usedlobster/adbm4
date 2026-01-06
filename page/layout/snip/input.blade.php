<div class="relative">
    @if ( isset($label) )
    <label for="{{ $id }}" class="wd-label">{!! $ui->label( $label , $info ?? false  ) !!}</label>
    @endif
    <div class="relative flex items-center space-x-2 ">
        <input id="{{ $id }}"
               name="{{ $id }}"
               class="wd-input"
               type="text"
               @if(isset($disabled) && $disabled ) disabled @endif
               @if(isset($value)) value="{{ $value }}" @endif
               @if(isset($model)) x-model="{{ $model }}" @endif
               @if(isset($input)) @input.debounce.50="{{ $input }}" @endif
               @if(isset($autocomplete)) autocomplete="{{ $autocomplete ?? '' }}" @endif
               placeholder="{{ $placeholder ?? '' }}">
    </div>
</div>