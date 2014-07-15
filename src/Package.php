<?php
namespace Pickle;

use Composer\Package\CompletePackage;

class Package extends CompletePackage
{
    /**
     * @var string Package's root directory
     */
    private $path;

    /**
     * Get the package's root directory
     *
     * @return string
     */
    public function getRootDir()
    {
        return $this->path;
    }

    /**
     * Get the package's root directory
     *
     * @return string
     */
    public function getSourceDir()
    {
        $path = $this->getRootDir();
        $release = $path . DIRECTORY_SEPARATOR . $this->getPrettyName() . '-' . $this->getPrettyVersion();

        if (is_dir($release)) {
            $path = $release;
        }

        return $path;
    }

    /**
     * Set the package's source directory, containing config.m4/config.w32
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
            $config = file_get_contents($this->getSourceDir() . '/config.w32');
            $options['with'] = $this->fetchArgWindows('ARG_WITH', $config);
            $options['enable'] = $this->fetchArgWindows('ARG_ENABLE', $config);
        } else {
            $configs = glob($this->getSourceDir() . '/' . 'config*.m4');
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    $config = file_get_contents($config);
                    $options['with'] = $this->fetchArg('PHP_ARG_WITH', $config);
                    $acArgumentWith = $this->fetchArgAc('AC_ARG_WITH', $config);
                    $options['with'] = array_merge($options['with'], $acArgumentWith);

                    $options['enable'] = $this->fetchArg('PHP_ARG_ENABLE', $config);
                    $acArgumentEnable = $this->fetchArgAc('AC_ARG_ENABLE', $config);
                    $options['enable'] = array_merge($options['enable'], $acArgumentEnable);
                }
            }
        }
        return array_merge($options['with'], $options['enable']);
    }

    /**
     *
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
                $a = str_replace([')', "'"], ['',''], $a);
                $a = trim($a);
            });

            @list($name, $prompt, $default) = $elems;
            $name = str_replace('"', '', $name);
            $options[$name] = (object) [
                'prompt'  => $prompt,
                'type'    => $type,
                'default' => $default
            ];
            $next = $e + 1;
        }

        return $options;
    }

    /**
     *
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

            $desc = preg_replace('![\s]+!', ' ', trim($desc));
            $desc = trim(substr($desc, 1, strlen($desc) - 2));

            $s_a = strpos($desc, ' ');
            $desc = trim(substr($desc, $s_a));

            $options[$name] = (object) [
                'prompt'  => trim($desc),
                'type'    => $type,
                'default' => $default
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
            if ('enable' == $type) {
                $default = (false !== strpos($option, '-disable-')) ? true : false;
            } elseif ('with' == $type) {
                $default = (false !== strpos($option, '-without-')) ? true : false;
            }
            $name = str_replace(['[',']'], ['',''], $name);
            $options[$name] = (object) [
                'prompt'  => trim($desc),
                'type'    => $type,
                'default' => $default
            ];
            $next = $e + 1;
        }

        return $options;
    }

    /**
     * Get files, will not return gitignore files
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
        $headers = glob($this->path . DIRECTORY_SEPARATOR . '*.h');
        $ext_name = $this->getName();
        $version_define = 'PHP_' . strtoupper($ext_name) . '_VERSION';
        foreach ($headers as $header) {
            $contents = @file_get_contents($header);
            if (!$contents) {
                Throw new \Exception("Cannot read header <$header>");
            }
            $pos_version = strpos($contents, $version_define);
            if ($pos_version !== FALSE) {
                $nl = strpos($contents, "\n", $pos_version);
                $version_line = trim(substr($contents, $pos_version, $nl - $pos_version ));
                list($version_define, $version) = explode(' ', $version_line);
                $version = trim(str_replace('"', '', $version));
                break;
            }
        }
        if (empty($version)) {
            Throw new \Exception('No ' . $version_define . ' can be found');
        }

        return [trim($version_define), $version];
    }
}
