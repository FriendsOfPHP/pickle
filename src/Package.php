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
        $config = file_get_contents($this->path . '/config.m4');
        $options['with'] = $this->fetchArg('PHP_ARG_WITH', $config);
        $acArgumentWith = $this->fetchArgAc('AC_ARG_WITH', $config);
        $options['with'] = array_merge($options['with'], $acArgumentWith);

        $options['enable'] = $this->fetchArg('PHP_ARG_ENABLE', $config);
        $acArgumentEnable = $this->fetchArgAc('AC_ARG_ENABLE', $config);
        $options['enable'] = array_merge($options['enable'], $acArgumentEnable);

        $this->configureOptions = array_merge($options['with'], $options['enable'], $this->configureOptions);
        $this->extra["configure-options"] = $this->configureOptions;

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
        $ignoreFiles = $this->getGitIgnoreFiles();
        $all = $files = array();
        $dir = $this->path;
        while ($dirs = glob($dir . '*')) {
            $dir .= '/*';
            $files = array_diff($all, $ignoreFiles);
            if (!$all) {
                $all = $dirs;
            } else {
                $all = array_merge($all, $dirs);
            }
        }

        return $files;
    }
}
