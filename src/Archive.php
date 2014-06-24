<?php
namespace Pickle;

class Archive
{
    /**
     * @var string
     */
    private $pkg;

    /**
     * Constructor
     *
     * @param Package $package
     * @param string $path
     */
    public function __construct(Package $package, $path = '')
    {
        $this->pkg = $package;
    }

    /**
     * Add directory
     *
     * @param string $arch
     * @param string $path
     * @return void
     */
    protected function addDir($arch, $path)
    {
        foreach ($this->pkg->getFiles() as $file) {
            if (is_dir($path)) {
                $arch->addDir($path);
            } else {
                $name = str_replace($this->pkg->getRootDir(), '', $file);
                $arch->addFile($path, $name);
            }
        }
    }

    /**
     * Create package
     */
    public function create()
    {
        $arch_basename = $this->pkg->getName() . '-' . $this->pkg->getVersion();

        /* Work around bug  #67417 [NEW]: ::compress modifies archive basename
        creates temp file and rename it */
        $tempname = getcwd() . '/pkl-tmp.tar';
        $arch = new \PharData($tempname);
        $pkg_dir = $this->pkg->getRootDir();

        foreach ($this->pkg->getFiles() as $file) {
            if (is_dir($file)) {
                //$arch->addDir($file);
            } else {
                $name = str_replace($pkg_dir, '', $file);
                $arch->addFile($file, $name);
            }
        }

        $this->pkg->getStatus();
        $this->pkg->getAuthors();
        $this->pkg->getConfigureOptions();
        $json = $this->pkg->getReleaseJson();
        $arch->addFromString('pickle.json', $json);
        $arch->compress(\Phar::GZ);
        unset($arch);
        rename($tempname . '.gz', $arch_basename . '.tgz');
        unlink($tempname);

    }
}
