<?php
namespace Pickle\Base\Util;

trait FileOps
{
    protected $tempDir = null;

    public function createTempDir($name = '')
    {
        $tmp = TmpDir::get();
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
