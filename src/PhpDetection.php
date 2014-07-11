<?php
namespace pickle;

class PhpDetection
{
    private $phpCli;
    private $phpize;
    private $version;
    private $major;
    private $minor;
    private $release;
    private $extra;
    private $compiler;
    private $architecture;
    private $zts;
    private $debug;
    private $iniPath;
    private $extensionDir;
    private $hasSdk;

    public function __construct($phpCli = PHP_BINARY)
    {
        if (!(is_file($phpCli) && is_executable($phpCli))) {
            throw new \Exception("Invalid php executable: $phpCli");
        }
        $this->phpCli = $phpCli;
        $this->getFromConstants();
    }

    private function getFromConstants()
    {
        $script = 'echo PHP_VERSION . \"\n\";
        echo PHP_MAJOR_VERSION . \"\n\";
        echo PHP_MINOR_VERSION . \"\n\";
        echo PHP_RELEASE_VERSION . \"\n\";
        echo PHP_EXTRA_VERSION . \"\n\";
        echo PHP_ZTS . \"\n\";
        echo PHP_DEBUG . \"\n\";
        ';

        $cmd = $this->phpCli . ' -r ' . '"' . str_replace("\n", '', $script) . '"';

        exec($cmd, $info);
        list($this->version, $this->major, $this->minor, $this->release, $this->extra, $this->zts, $this->debug) = $info;
        list($this->compiler, $this->architecture, $this->iniPath, $this->extensionDir) = $this->getFromPhpInfo();
    }

    private function getFromPhpInfo()
    {
        $cmd = $this->phpCli . ' -i';
        exec($cmd, $info);
        $extensionDir = $compiler = $arch = $iniPath = '';
        if (!is_array($info)) {
            throw new \Exception('Cannot parse phpinfo output');
        }
        foreach ($info as $s) {
            if (false !== strpos($s, 'extension_dir')) {
                list(, $extensionDir,) = explode('=>', $s);
                continue;
            }
            if (false !== strpos($s, "Loaded Configuration File")) {
                list(, $iniPath) = explode('=>', $s);
                if ('(None)' === $iniPath) {
                    $iniPath = '';
                }
                continue;
            }
            if (false === strpos($s, 'Architecture')) {
                if (false === strpos($s, 'Compiler')) {
                    continue;
                }
                list(, $compiler) = explode('=>', $s);
            } else {
                list(, $arch) = explode('=>', $s);
            }

        }
        $arch = trim($arch);
        $iniPath = trim($iniPath);
        $compiler = trim($compiler);
        $extensionDir = trim($extensionDir);

        $compiler = trim(strtolower(str_replace('MS', '', substr($compiler, 0, 6))));
        if (!$iniPath) {
            throw new \Exception('Cannot detect php.ini directory');
        }
        if (!$arch) {
            throw new \Exception('Cannot detect PHP build architecture');
        }
        if (!$compiler) {
            throw new \Exception('Cannot detect PHP build compiler version');
        }
        if (!$extensionDir) {
            throw new \Exception('Cannot detect PHP extension directory');
        }

        return [$compiler, $arch, $iniPath, $extensionDir];
    }

    public function hasSdk()
    {
        if (isset($this->hasSdk)) {
            return $this->hasSdk;
        }
        $cliDir = dirname($this->phpCli);
        $res = glob($cliDir . DIRECTORY_SEPARATOR . 'phpize*');
        if (!$res) {
            $this->hasSdk = false;
        }
        $this->phpize = $res[0];

        return ($this->hasSdk = false);
    }

    public function getArchitecture()
    {
        return $this->architecture;
    }

    public function getCompiler()
    {
        return $this->compiler;
    }

    public function getPhpCliPath()
    {
        return $this->phpCli;
    }

    public function getMajorVersion()
    {
        return $this->major;
    }

    public function getMinorVersion()
    {
        return $this->minor;
    }

    public function getReleaseVersion()
    {
        return $this->release;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getZts()
    {
        return $this->zts;
    }

    public function getExtensionDir()
    {
        return $this->extensionDir;
    }

    public function getPhpIniDir()
    {
        return $this->iniPath;
    }
}
