<?php

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('c:/laragon/www/herbigreen-app/app/Filament'));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        if (strpos($content, 'protected static ?string $navigationGroup') !== false) {
            $content = str_replace('protected static ?string $navigationGroup', 'protected static string|\UnitEnum|null $navigationGroup', $content);
            file_put_contents($path, $content);
            echo 'Fixed: ' . $path . PHP_EOL;
        }
    }
}
