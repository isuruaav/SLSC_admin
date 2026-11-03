<?php

$source = dirname(__DIR__).'/themes/slsc/lang/interface.json';
$translations = json_decode(file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
foreach (array_keys($translations['en']) as $key) {
    if (! isset($translations['si'][$key]) || preg_match('/\?{2}|\x{FFFD}/u', $translations['si'][$key])) {
        throw new RuntimeException('Missing or damaged Sinhala interface translation: '.$key);
    }
}
file_put_contents(dirname(__DIR__).'/themes/slsc/assets/js/i18n.js',
    '// Generated from themes/slsc/lang/interface.json. Run php scripts/sync-slsc-translations.php after editing. '.PHP_EOL
    .'export const translations = '.json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).';'.PHP_EOL);
echo 'Synchronized '.count($translations['en']).' English/Sinhala interface labels.'.PHP_EOL;
