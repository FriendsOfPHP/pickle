<?php
namespace pickle;

class PhpDetection
{
    private $php_cli;
    private $version;
    private $major;
    private $minor;
    private $release;
    private $extra;
    private $compiler;
    private $architecture;
    private $zts;
    private $debug;
    private $ini_path;
    private $extension_dir;

    public function __construct($php_cli = PHP_BINARY)
    {
        if (!(is_file($php_cli) && is_executable($php_cli))) {
            Throw new \Exception("Invalid php executable: $php_cli");
        }
        $this->php_cli = $php_cli;
        $this->_getFromConstants();
    }

    private function _getFromConstants()
    {
        $script = 'echo PHP_VERSION . \"\n\";
        echo PHP_MAJOR_VERSION . \"\n\";
        echo PHP_MINOR_VERSION . \"\n\";
        echo PHP_RELEASE_VERSION . \"\n\";
        echo PHP_EXTRA_VERSION . \"\n\";
        echo PHP_ZTS . \"\n\";
        echo PHP_DEBUG . \"\n\";
        ';

        $cmd = $this->php_cli . ' -r ' . '"' . str_replace("\n",'', $script) . '"';

        exec($cmd, $info);
        list($this->version, $this->major, $this->minor, $this->release, $this->extra, $this->zts, $this->debug) = $info;
        list($this->compiler, $this->architecture, $this->ini_path, $this->extension_dir) = $this->_getFromPhpInfo();
    }

    private function _getFromPhpInfo()
    {
        $cmd = $this->php_cli . ' -i';
        exec($cmd, $info);
        $extension_dir = $compiler = $arch = $ini_path = '';
		if (!is_array($info)) {
			Throw new \Exception('Cannot parse phpinfo output');
		}
        foreach ($info as $s) {
            if (strpos($s, 'extension_dir') !== FALSE) {
                list(, $extension_dir,) = explode('=>', $s);
                continue;
            }
            if (strpos($s, "Loaded Configuration File") !== FALSE) {
                list(, $ini_path) = explode('=>', $s);
                if ($ini_path == "(None)") {
                    $ini_path = '';
                }
                continue;
            }
            if (strpos($s, 'Architecture') === FALSE) {
                if (strpos($s, 'Compiler') === FALSE) {
                    continue;
                }
                list(, $compiler) = explode('=>', $s);
            } else {
                list(, $arch) = explode('=>', $s);
            }

        }
        $arch = trim($arch);
        $ini_path = trim($ini_path);
        $compiler = trim($compiler);
        $extension_dir = trim($extension_dir);
        $compiler = strtolower(str_replace('MS', '', substr($compiler, 0, 6)));
        if (!$ini_path) {
            Throw new \Exception('Cannot detect php.ini directory');
        }
        if (!$arch) {
            Throw new \Exception('Cannot detect PHP build architecture');
        }
        if (!$compiler) {
            Throw new \Exception('Cannot detect PHP build compiler version');
        }
        if (!$extension_dir) {
            Throw new \Exception('Cannot detect PHP extension directory');
        }

        return [$compiler, $arch, $ini_path, $extension_dir];
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
        return $this->php_cli;
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
        return $this->extension_dir;
    }

    public function getPhpIniDir()
    {
        return $this->ini_path;
    }
}
