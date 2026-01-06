<?php

    namespace sys\blade;

    class UI {

        public function label(string $label, $info = false): string
        {
            if (!$info || !str_contains($label, '(i)')) {
                return $label;
            }

            $safeInfo = htmlspecialchars($info, ENT_QUOTES);
            $icon = sprintf(
                    '<button type="button" class="inline-flex p-0 focus:outline-none" x-init="tippy($el,{trigger:\'click\',content:\'%s\'})">' .
                    '<span aria-label="Info:%s" class="text-blue-500 hover:text-blue-700 transition-colors font-bold" ' .
                    'style="font-size: 0.9rem; transform: translateY(-0.25rem); margin-left: 0.2rem;">' .
                    'ⓘ</span></button>',
                    $safeInfo,
                    $safeInfo
            );

            return str_replace('(i)', $icon, $label);

        }
    }