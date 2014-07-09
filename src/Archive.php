<?php
namespace Pickle;

use Pickle\Package\JSON\Dumper;

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
        $tempname = getcwd() . '/pkl-tmp.tar';
        $arch = new \PharData($tempname);
        $pkg_dir = $this->pkg->getRootDir();

        foreach ($this->pkg->getFiles() as $file) {
            if (is_file($file)) {
                $name = str_replace($pkg_dir, '', $file);
                $arch->addFile($file, $name);
            }
        }

        $arch->compress(\Phar::GZ);
        unset($arch);
        rename($tempname . '.gz', $archBasename . '.tgz');
        unlink($tempname);
    }
}
