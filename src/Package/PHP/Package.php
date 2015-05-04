<?php

namespace Pickle\Package\PHP;

use Composer\Package\CompletePackage;
use Pickle\Base\Util\GitIgnore;

class Package extends CompletePackage implements \Pickle\Base\Interfaces\Package
{
    /**
     * @var string Package's root directory
     */
    protected $path;

    /**
     * Get the package's root directory.
     *
     * @return string
     */
    public function getRootDir()
    {
        return $this->path;
    }

    /**
     * Get the package's root directory.
     *
     * @return string
     */
    public function getSourceDir()
    {
        $path = $this->getRootDir();
        $release = $path.DIRECTORY_SEPARATOR.$this->getPrettyName().'-'.$this->getPrettyVersion();

        if (is_dir($release)) {
            $path = $release;
        }

    /* Do subdir search */
    if (!$this->extConfigIsIn($path)) {
        $path = $this->locateSourceDirByExtConfig($path);

        if (null === $path) {
            throw new \Exception('config*.(m4|w32) not found');
        }
    }

        return $path;
    }

    /**
     * Set the package's source directory, containing config.m4/config.w32.
     *
     * @param string $path
     */
    public function setRootDir($path)
    {
        $this->path = $path;
    }

    public function setStability($stability)
    {
        $this->stability = $stability;
    }

    /**
     * @return array
     */
    public function getConfigureOptions()
    {
        $options = [];

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $config_file = $this->getSourceDir().'/config.w32';

            if (!file_exists($config_file)) {
                throw new \Exception('cnofig.w32 not found');
            }

            $config = file_get_contents($config_file);

            $options = array_merge(
                $this->fetchArgWindows('ARG_WITH', $config),
                $this->fetchArgWindows('ARG_ENABLE', $config)
            );
        } else {
            $configs = glob($this->getSourceDir().'/'.'config*.m4');

            if (!empty($configs)) {
                foreach ($configs as $config) {
                    $options = array_merge($options, $this->getConfigureOptionsFromFile($config));
                }
            }
        }

        return $options;
    }

    public function getConfigureOptionsFromFile($file)
    {
        $config = file_get_contents($file);

        return array_merge(
            $this->fetchArg('PHP_ARG_WITH', $config),
            $this->fetchArgAc('AC_ARG_WITH', $config),
            $this->fetchArg('PHP_ARG_ENABLE', $config),
            $this->fetchArgAc('AC_ARG_ENABLE', $config)
        );
    }

    /**
     * @param string $which
     * @param string $config
     *
     * @return array
     */
    protected function fetchArgWindows($which, $config)
    {
        $next = 0;
        $options = [];
        $type = false !== strpos($which, 'ENABLE')  ? 'enable' : 'with';
        while (false !== ($s = strpos($config, $which, $next))) {
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            $elems = explode(',', $option);
            array_walk($elems, function (&$a) {
                $a = str_replace([')', "'"], ['', ''], $a);
                $a = trim($a);
            });

            @list($name, $prompt, $default) = $elems;
            $name = str_replace('"', '', $name);
            $options[$name] = (object) [
                'prompt'  => $prompt,
                'type'    => $type,
                'default' => $default,
            ];
            $next = $e + 1;
        }

        return $options;
    }

    /**
     * @param string $which
     * @param string $config
     *
     * @return array
     */
    protected function fetchArgAc($which, $config)
    {
        $next = 0;
        $options = [];
        $type = false !== strpos($which, 'ENABLE')  ? 'enable' : 'with';
        while (false !== ($s = strpos($config, $which, $next))) {
            $default = true;
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            if ('enable' == $type) {
                $default = (false !== strpos($option, '-disable-')) ? true : false;
            } elseif ('with' == $type) {
                $default = (false !== strpos($option, '-without-')) ? true : false;
            }

            list($name, $desc) = explode(',', $option);

            $desc = preg_replace('/\s+/', ' ', trim($desc));
            $desc = trim(substr($desc, 1, strlen($desc) - 2));
            $s_a = strpos($desc, ' ');
            $desc = trim(substr($desc, $s_a));

            $options[$name] = (object) [
                'prompt'  => $desc,
                'type'    => $type,
                'default' => $default,
            ];

            $next = $e + 1;
        }

        return $options;
    }

    /**
     * @param string $which
     * @param string $config
     *
     * @return array
     */
    protected function fetchArg($which, $config)
    {
        $next = 0;
        $options = [];

        $type = false !== strpos($which, 'ENABLE') ? 'enable' : 'with';
        while (false !== ($s = strpos($config, $which, $next))) {
            $default = 'y';
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            list($name, $desc) = explode(',', $option);

            /* Description can be part of the 3rd argument */
            if (empty($desc) || $desc === '[]') {
                list($name, , $desc) = explode(',', $option);
                $desc = preg_replace('/\s+/', ' ', trim($desc));
                $desc = trim(substr($desc, 1, strlen($desc) - 2));
                $desc = trim(str_replace(['[', ']'], ['', ''], $desc));
                $s_a = strpos($desc, ' ');
                $desc = trim(substr($desc, $s_a));
            }

            if ('enable' == $type) {
                $default = (false !== strpos($option, '-disable-')) ? true : false;
            } elseif ('with' == $type) {
                $default = (false !== strpos($option, '-without-')) ? true : false;
            }
            $name = str_replace(['[', ']'], ['', ''], $name);
            $options[$name] = (object) [
                'prompt'  => trim($desc),
                'type'    => $type,
                'default' => $default,
            ];
            $next = $e + 1;
        }

        return $options;
    }

    /**
     * Get files, will not return gitignore files.
     *
     * @return \CallbackFilterIterator
     */
    public function getFiles()
    {
        return new \CallbackFilterIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getSourceDir())
            ),
            new GitIgnore($this)
        );
    }

    public function getVersionFromHeader()
    {
        $headers = glob($this->path.DIRECTORY_SEPARATOR.'*.h');
        $ext_name = $this->getName();
        $version_define = 'PHP_'.strtoupper($ext_name).'_VERSION';
        foreach ($headers as $header) {
            $contents = @file_get_contents($header);
            if (!$contents) {
                throw new \Exception("Cannot read header <$header>");
            }
            $pos_version = strpos($contents, $version_define);
            if ($pos_version !== false) {
                $nl = strpos($contents, "\n", $pos_version);
                $version_line = trim(substr($contents, $pos_version, $nl - $pos_version));
                list($version_define, $version) = explode(' ', $version_line);
                $version = trim(str_replace('"', '', $version));
                break;
            }
        }
        if (empty($version)) {
            throw new \Exception('No '.$version_define.' can be found');
        }

        return [trim($version_define), $version];
    }

    protected function extConfigIsIn($path)
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR') !== false) {
            return file_exists(realpath($path).DIRECTORY_SEPARATOR.'config.w32');
        } else {
            $r = glob("$path/config*.m4");

            return (is_array($r) && !empty($r));
        }
    }

    protected function locateSourceDirByExtConfig($path)
    {
        $it = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path),
        \RecursiveIteratorIterator::SELF_FIRST
    );

        foreach ($it as $fl_obj) {
            if ($fl_obj->isFile() && preg_match(',config*.(m4|w32),', $fl_obj->getBasename())) {
                return $fl_obj->getPath();
            }
        }

        return;
    }
}
