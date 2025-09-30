<button type="submit"
    :disabled="{!! $check or '' !!}"
    name="{!! $name !!}" value="{!! $value or 1 !!}" id="{!! $name !!}"

    class="group bar-button">
    {!! $slot !!}
</button>