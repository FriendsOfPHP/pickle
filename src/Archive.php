<?php
namespace Pickle;

class Archive
{
    /**
     * @var Package
     */
    private $pkg;

    /**
     * Constructor
     *
     * @param Package $package
     */
    public function __construct(Package $package)
    {
        $this->pkg = $package;
    }

    /**
     * Create package
     */
    public function create()
    {
        $archBasename = $this->pkg->getName() . '-' . $this->pkg->getPrettyVersion();

        /* Work around bug  #67417 [NEW]: ::compress modifies archive basename
        creates temp file and rename it */
        $tempName = getcwd() . '/pkl-tmp.tar';
        if (file_exists($tempName)) {
            unlink($tempName);
        }
        $arch = new \PharData($tempName);
        $pkgDir = $this->pkg->getRootDir();

        foreach ($this->pkg->getFiles() as $file) {
            if (is_file($file)) {
                $name = str_replace($pkgDir, '', $file);
                $arch->addFile($file, $name);
            }
        }
        if (file_exists($tempName)) {
            @unlink($tempName . '.gz');
        }
        $arch->compress(\Phar::GZ);
        unset($arch);

        rename($tempName . '.gz', $archBasename . '.tgz');
        unlink($tempName);
    }
}
