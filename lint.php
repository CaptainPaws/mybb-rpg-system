<?php
// php lint.php
$directory = $argv[1] ?? __DIR__;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$errors = 0;

foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $output = [];
    $code = 0;
    exec("php -l " . escapeshellarg($path), $output, $code);
    if ($code !== 0) {
        echo "❌  {$path}\n" . implode("\n", $output) . "\n\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "✅  Всё чисто!\n";
    exit(0);
}
echo "💥  Найдено ошибок: {$errors}\n";
exit(1);
