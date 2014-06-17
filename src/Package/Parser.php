<?php
namespace Pickle\Package;

abstract class Parser
{
    /**
     * @var string Package's root directory
     */
    protected $root;

    private $configureOptions;

    /**
     * Constructor
     *
     * @param string $path Path to the package root directory
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($path)
    {
        $this->root = $path;

        if (is_dir($this->root) === false) {
            throw new \InvalidArgumentException('Directory not found: ' . $path);
        }
    }

    abstract public function getName();

    abstract public function getVersion();

    abstract public function getStatus();

    abstract public function getAuthors();

    abstract public function getSummary();

    abstract public function getDescription();

    abstract public function getCurrentRelease();

    abstract public function getPastReleases();

    abstract public function getExtraOptions();

    /**
     * Get configurable options
     *
     * @return array
     */
    public function getConfigureOptions()
    {
        if (!null !== $this->configureOptions) {
            $config = file_get_contents($this->root . '/config.m4');
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

    /* If someone prefers a nice regex for both AC_ and PHP_... :) */
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
}
