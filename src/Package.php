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
            $config = file_get_contents($this->getSourceDir() . '/config.m4');
            $options['with'] = $this->fetchArg('PHP_ARG_WITH', $config);
            $acArgumentWith = $this->fetchArgAc('AC_ARG_WITH', $config);
            $options['with'] = array_merge($options['with'], $acArgumentWith);

            $options['enable'] = $this->fetchArg('PHP_ARG_ENABLE', $config);
            $acArgumentEnable = $this->fetchArgAc('AC_ARG_ENABLE', $config);
            $options['enable'] = array_merge($options['enable'], $acArgumentEnable);
        }

        return array_merge($options['with'], $options['enable']);
    }

    /**
     * @todo If someone prefers a nice regex for both AC_ and PHP_... :)
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
        //ARG_ENABLE('apcu', 'Whether to enable APCu support', 'no');
        $type = false !== strpos($which, 'ENABLE')  ? 'enable' : 'with';
        while (false !== ($s = strpos($config, $which, $next))) {
            $default = true;
            $s = strpos($config, '(', $s);
            $e = strpos($config, ')', $s + 1);
            $option = substr($config, $s + 1, $e - $s);

            $elems = explode(',', $option);
            array_walk($elems, function (&$a) {
                $a = str_replace([')', "'"], ['',''], $a);
                $a = trim($a);
            });

            list($name, $prompt, $default) = $elems;

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

    public function getChangelog()
    {

    }
}
