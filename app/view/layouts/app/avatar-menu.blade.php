<?php $info = $_SESSION['_user_info_'] ?? (isset($app) ? $app->getUserProfile() : []) ?>
<div class="relative inline-flex justify-between header-icon" x-data="{ open:false }">
    <button
            class="inline-flex justify-start items-center group"
            aria-haspopup="true"
            @click.prevent="open = !open"
            :aria-expanded="open"
    >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="button-hover flex h-8 w-8 text-green-400 dark:text-blue-500  fill-current"
             viewBox="0 0 24 24">
            <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
        </svg>
    </button>
    <div
            class="origin-top-right z-10 absolute top-full right-0 min-w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1"
            @click.outside="open = false"
            @keydown.escape.window="open = false"
            x-show="open"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-out duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
    >
        <div class="pt-0.5 pb-2 px-3 mb-1 border-b border-gray-200 dark:border-gray-700/60">
            <div class="font-bold">{{ $info['dispname'] ?? 'Guest'  }}</div>
            <div class="font-medium">{{ $info['compname'] ?? 'Guest'  }}</div>
            <div class="text-xs italic">{{ $info['rolename'] ?? 'Guest' }} ({{ $info['level'] ?? '0' }})</div>
        </div>
        <ul>
            <li>
                <a class="flex items-center py-1 px-3" href="settings.html"
                   @click="open = false" @focus="open = true"
                   @focusout="open = false">Profile...</a>
            </li>
            <li>
                <a class="flex items-center py-1 px-3" href="/auth/signout"
                   @click="open = false" @focus="open = true" @focusout="open = false">Sign Out</a>
            </li>
        </ul>
    </div>
</div>