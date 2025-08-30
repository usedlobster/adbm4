<?php

    namespace PHPSTORM_META {
        override(sql_injection_subst(),
                map([
                        '<DB>' => 'adbm4_master',
                        '<DB_FAIL>' => 'adbm4_master.sys_fail'
                ]));
    }
