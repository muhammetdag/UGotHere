<?php
function loadLanguage(string $langCode = 'tr'): array {
    $fallback = 'tr';
    $langFile = __DIR__ . "/../assets/lang/$langCode.php";

    if (!file_exists($langFile)) {
        $langFile = __DIR__ . "/../assets/lang/$fallback.php";
    }

    return include $langFile;
}
