<?php
namespace Pickle;

trait FileOps
{
    protected $tempDir = null;

    public function createTempDir($name = '')
    {
        $tmp = sys_get_temp_dir();
        if (!$name) {
            $name = md5(uniqid());
        }
        $tempDir = $tmp . '/pickle-' . $name;

        if (is_dir($tempDir)) {
            $this->cleanup();
        }
        if (!is_dir($tempDir)) {
            mkdir($tempDir);
        }
        $this->tempDir = $tempDir;
    }

    public function cleanup()
    {
        if (is_dir($this->tempDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->tempDir,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                if ($path->isDir()) {
                    rmdir($path->getPathname());
                } else {
                    unlink($path->getPathname());
                }
            }
            rmdir($this->tempDir);
        }
    }
}
