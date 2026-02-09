<div class="relative">
    @if ( isset($label))
        <label for="{{ $id }}" class="wd-label">{!! $ui?->label( $label , $info ?? false  ) !!}</label>
    @endif
    <div class="relative flex items-center space-x-2 ">
        <input id="{{ $id }}"
               name="{{ $id }}"
               class="wd-input"
               type="password"
               value="{{ $value }}"
               @if(isset($model)) x-model="{{ $model }}" @endif
               @if(isset($input)) @input.debounce.50="{{ $input }}" @endif
               autocomplete="{{ $autocomplete ?? '' }}"
               placeholder="{{ $placeholder ?? 'Password' }}">
    </div>
</div>
