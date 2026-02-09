<?php
session_start() ;
$token = $_SESSION['_user']?->atkn ?? '' ;
echo <<<JS
window.wdAuth = {
    token: "{$token}",
    refreshUrl: "/boot/token-refresh.php" // Point to your PHP route
};
JS;

