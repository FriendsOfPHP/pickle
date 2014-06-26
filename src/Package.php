<?php
namespace Pickle;

use Composer\Package\CompletePackage;

class Package extends CompletePackage
{
    use GitIgnore;

    /**
     * @var string Package's root directory
     */
    private $path;

    private $configureOptions = [];

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
     * Set the package's root directory
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
        if (!null !== $this->configureOptions) {
            $config = file_get_contents($this->path . '/config.m4');
            $options['with'] = $this->fetchArg('PHP_ARG_WITH', $config);
            $t = $this->fetchArgAc('AC_ARG_WITH', $config);
            $options['with'] = array_merge($options['with'], $t);

            $options['enable'] = $this->fetchArg('PHP_ARG_ENABLE', $config);
            $t = $this->fetchArgAc('AC_ARG_ENABLE', $config);
            $options['enable'] = array_merge($options['enable'], $t);

            $this->configureOptions = $options;
        }

        return $this->configureOptions;
    }

    /**
     * @todo If someone prefers a nice regex for both AC_ and PHP_... :)
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
        $type = strpos($which, 'ENABLE') !== FALSE ? 'enable' : 'with';
        $default = true;
        while (($s = strpos($config, $which, $next)) !== FALSE) {
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            if ($type == 'enable') {
                $default = (strpos($option, '-disable-') !== false) ? true : false;
            } elseif ($type == 'with') {
                $default = (strpos($option, '-without-') !== false) ? true : false;
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

        $type = strpos($which, 'ENABLE') !== FALSE ? 'enable' : 'with';
        $default = 'y';
        while (($s = strpos($config, $which, $next)) !== FALSE) {
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);
            list($name, $desc) = explode(',', $option);

            if ($type == 'enable') {
                $default = (strpos($option, '-disable-') !== false) ? true : false;
            } elseif ($type == 'with') {
                $default = (strpos($option, '-without-') !== false) ? true : false;
            }


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
     * @return array
     */
    public function getFiles()
    {
        $ignorefiles = $this->getGitIgnoreFiles();
        $all = $files = array();
        $dir = $this->path;
        while ($dirs = glob($dir . '*')) {
            $dir .= '/*';
            $files = array_diff($all, $ignorefiles);
            if (!$all) {
                $all = $dirs;
            } else {
                $all = array_merge($all, $dirs);
            }
        }

        return $files;
    }
}
