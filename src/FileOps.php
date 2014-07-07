<?php
namespace Pickle;

trait FileOps
{
    public function createTempDir($name = '')
    {
        $tmp = sys_get_temp_dir();
        if ($name != '') {
            $tempDir = $tmp . '/pickle-' . $name;
        } else {
            $tempDir = $tmp . '/pickle-' . $this->pkg->getName() . '' . $this->pkg->getVersion();
        }

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
