<?php

/** Hash controlled text consistently across checkouts; preserve binary bytes. */
function controlledProjectFileHash(string $path): string
{
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException('Cannot read controlled file: '.$path);
    }

    // Git stores these text files with LF even when an editor writes CRLF.
    $textExtensions = ['md', 'json', 'php', 'js', 'css', 'html', 'yml', 'yaml', 'xml', 'txt', 'svg'];
    $textNames = ['artisan', '.env.example', '.gitignore', '.gitattributes', '.editorconfig', '.nvmrc'];
    if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $textExtensions, true)
        || in_array(basename($path), $textNames, true)) {
        $content = str_replace("\r\n", "\n", $content);
    }

    return hash('sha256', $content);
}

/** Return the source/config/document paths covered by architecture inventory. */
function controlledProjectFiles(string $root): array
{
    $paths = [];
    $directories = ['app', 'bootstrap', 'config', 'database', 'docs', 'public', 'resources', 'routes', 'scripts', 'tests', '.github'];
    $excluded = ['bootstrap/cache', 'public/build', 'public/hot', 'public/storage', 'docs/testing/local-results'];

    foreach ($directories as $directory) {
        $base = $root.'/'.$directory;
        if (! is_dir($base)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = str_replace(chr(92), '/', substr($file->getPathname(), strlen($root) + 1));
            if ($relative === 'docs/architecture/INVENTORY.json' || str_ends_with($relative, '.sqlite') || str_ends_with($relative, '.sqlite-journal')) {
                continue;
            }
            foreach ($excluded as $prefix) {
                if ($relative === $prefix || str_starts_with($relative, $prefix.'/')) {
                    continue 2;
                }
            }
            $paths[] = $relative;
        }
    }

    foreach (['README.md', 'AGENTS.md', 'CLAUDE.md', 'ARCHITECTURE.md', 'CONTRIBUTING.md', 'CHANGELOG.md', 'SRS.md', 'Luong-nghiep-vu-website-ban-xe-v6.docx', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'artisan', 'phpunit.xml', 'vite.config.js', '.env.example', '.gitignore', '.gitattributes', '.editorconfig', '.nvmrc'] as $file) {
        if (is_file($root.'/'.$file)) {
            $paths[] = $file;
        }
    }
    sort($paths, SORT_STRING);

    return array_values(array_unique($paths));
}
