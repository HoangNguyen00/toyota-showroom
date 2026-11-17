<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/project-files.php';

class InventoryHashTest extends TestCase
{
    public function test_vite_hot_marker_is_excluded_from_controlled_project_files(): void
    {
        $directory = sys_get_temp_dir().'/toyota-inventory-'.bin2hex(random_bytes(8));
        mkdir($directory.'/public', 0777, true);
        try {
            file_put_contents($directory.'/public/hot', 'http://127.0.0.1:5173');
            file_put_contents($directory.'/public/fixture.txt', 'controlled source');
            $this->assertSame(['public/fixture.txt'], controlledProjectFiles($directory));
        } finally {
            unlink($directory.'/public/hot');
            unlink($directory.'/public/fixture.txt');
            rmdir($directory.'/public');
            rmdir($directory);
        }
    }

    public function test_text_line_endings_are_portable_but_content_changes_are_detected(): void
    {
        $directory = sys_get_temp_dir().'/toyota-inventory-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $path = $directory.'/example.md';
        try {
            file_put_contents($path, "first\nsecond\n");
            $expected = controlledProjectFileHash($path);
            file_put_contents($path, "first\r\nsecond\r\n");
            $this->assertSame($expected, controlledProjectFileHash($path));
            file_put_contents($path, "first\r\nchanged\r\n");
            $this->assertNotSame($expected, controlledProjectFileHash($path));
        } finally {
            unlink($path);
            rmdir($directory);
        }
    }

    public function test_binary_source_is_hashed_without_normalization(): void
    {
        $directory = sys_get_temp_dir().'/toyota-inventory-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $path = $directory.'/source.docx';
        try {
            $bytes = "PK\x00binary\r\npayload";
            file_put_contents($path, $bytes);
            $this->assertSame(hash('sha256', $bytes), controlledProjectFileHash($path));
            $this->assertNotSame(hash('sha256', str_replace("\r\n", "\n", $bytes)), controlledProjectFileHash($path));
        } finally {
            unlink($path);
            rmdir($directory);
        }
    }
}
