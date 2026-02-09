<div>

        <label for="{{ $id }}" class="wd-label">
            <input id="{{ $id }}"
                   name="{{ $id }}"
                   class="wd-checkbox mr-1"
                   {{ ( $value ? 'checked' : '') }}
                   type="checkbox"
            />
            {!! $ui?->label( $label , $info ??  '' ) ?? $label !!}
        </label>


</div>