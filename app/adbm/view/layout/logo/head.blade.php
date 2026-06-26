<div>
    <div class="max-w-xs mx-auto mb-2 flex justify-center">
        <a href="/" aria-label="Backto portal">
            <img class="w-24 h-24" src="/img/logo.svg" alt="ADBM Logo"/>
        </a>
    </div>
    @if ( isset( $title ))
        <h2 class="mt-2 sm:mt-3 text-center text-2xl font-extrabold text-gray-900">
            {!! $title !!}
        </h2>
    @endif
</div>