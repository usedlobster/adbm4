@if ( !empty($slot))
    <div x-data="{ show: false }"
         x-cloak
         x-init="setTimeout(() => {
            show = true ;
            @if ( isset( $timeout) )
                setTimeout(()=> show = false , {{$timeout}} ) ;
            @endif
            }  , 0 )"

         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="info-block {!! $type !!}"
         :class="{ 'animate-[shake_0.5s_ease-in-out]' : show }"
         role="alert"
         aria-live="assertive"
         aria-atomic="true">

        <div class="flex justify-between items-start">
            <div class="flex">
                <div class="flex-shrink-0 " aria-hidden="true">
                    <img class="svg-icon" src="/img/icons/{!! $type ?? 'basic' !!}.svg" />
                </div>
                <div class="ml-3 mt-1 info-text ">
                    {!! $slot !!}
                </div>

            </div>
            <button type="button" @click="show = false"
                    class="ml-4 inline-flex closebtn"
                    aria-label="Dismiss error message">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
@endif