<div class="relative">
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">{{ $label ?? 'Email' }}</label>
    <div class="relative flex items-center space-x-2 mr-12   ">
        <input id="{{ $id }}"
               name="{{ $id }}"
               type="text"
               value="{{ $value }}" d
               @if(isset($model)) x-model="{{ $model }}" @endif
               @if(isset($input)) @input.debounce.250="{{ $input }}" @endif
               autocomplete="{{ $autocomplete ?? 'current-password' }}"
               class="mt-1 block w-full rounded-md"
               placeholder="{{ $placeholder ?? 'Email' }}">
    </div>
</div>