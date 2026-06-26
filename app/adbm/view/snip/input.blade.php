<div class="relative">
    @if ( isset($label) )
        <?php \app\wd\UI::label( ($label[0] ?? '') , ($label[1] ?? '')   , ($label[2]  ?? '') ) ; ?>
    @endif
    <div class="relative flex items-center space-x-2">
        <input id="{{ $id }}"
               name="{{ $id }}"
               class="wd-input"
               type="{{ $type ?? 'text' }}"
               @if(isset($disabled) && $disabled ) disabled @endif
               @if(isset($value)) value="{{ $value }}" @endif
               @if(isset($model)) x-model="{{ $model }}" @endif
               @if(isset($input)) @input.debounce.50="{{ $input }}" @endif
               @if(isset($autocomplete)) autocomplete="{{ $autocomplete ?? '' }}" @endif
               @if(isset($autofocus) && $autofocus) autofocus @endif
               @if(isset($inputmode)) inputmode="{{$inputmode}}" @endif
               @if(isset($placeholder)) placeholder="{{$placeholder}}" @endif
               @if(isset($event1)) {!! $event1 !!}} @endif
               @if(!isset($noend ))
               onfocus="try{this.setSelectionRange(this.value.length,this.value.length);this.onfocus=null}catch(e){}"
               @endif
        />
    </div>
</div>