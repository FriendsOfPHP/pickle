<?php
namespace Pickle\Package;

abstract class Parser
{
    /**
     * @var string Package's root directory
     */
    protected $root;

    /**
     * @var array
     */
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

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return string
     */
    abstract public function getVersion();

    /**
     * @return string
     */
    abstract public function getStatus();

    /**
     * @return array
     */
    abstract public function getAuthors();

    /**
     * @return string
     */
    abstract public function getSummary();

    /**
     * @return string
     */
    abstract public function getDescription();

    /**
     * Get the current release.
     *
     * Release format:
     * [
     *     'version' => '2.0.0',
     *     'status' => 'stable',
     *     'date' => '2014-06-18',
     *     'notes' => 'Lorem ipsum',
     *     'api' => [
     *         'version' => '2.0.0',
     *         'status' => 'stable'
     *     ]
     * ]
     *
     * @return array
     */
    abstract public function getCurrentRelease();

    /**
     * Get previous releases
     *
     * @see Parser::getCurrentRelease
     *
     * @return array
     */
    abstract public function getPastReleases();

    /**
     * Get configurable options
     *
     * Options format:
     * [
     *     "option-name": [
     *         "default": "value",
     *         "prompt": "Option description"
     *     ]
     * ]
     *
     * @return array
     */
    abstract public function getExtraOptions();

    /**
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

    /**
     * @todo If someone prefers a nice regex for both AC_ and PHP_... :)
     *
     * @param $which
     * @param $config
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
     * @param $which
     * @param $config
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
}
