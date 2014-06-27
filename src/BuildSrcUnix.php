<?php
namespace Pickle;

class BuildSrcUnix
{
    private $pkg;
    private $options;
    private $log = '';
    private $cwdBack;
    private $buildDir;

    public function __construct(Package $pkg, $options = null)
    {
        $this->pkg = $pkg;
        $this->options = $options;
        $this->cwdBack = getcwd();
    }

    /**
     * @param integer $level
     * @param string $msg
     */
    public function log($level, $msg)
    {
        $this->log .= $level . ': ' . $msg . "\n";
    }

    public function createTempDir()
    {
        $tmp = sys_get_temp_dir();
        $buildDir = $tmp . '/pickle-' . $this->pkg->getName() . '' . $this->pkg->getVersion();

        if (is_dir($buildDir)) {
            $this->cleanup();
        }

        mkdir($buildDir);
        $this->buildDir = $buildDir;
    }

    public function cleanup()
    {
        if (is_dir($this->buildDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->buildDir,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                if ($path->isDir()) {
                    rmdir($path->getPathname());
                } else {
                    unlink($path->getPathname());
                }
                echo 'rmdir :' . $path->getPathname() . "\n";
            }
            rmdir($this->buildDir);
        }
    }

    public function phpize()
    {
        $backCwd = getcwd();
        chdir($this->pkg->getRootDir());

        $res = $this->runCommand('phpize');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('phpize failed');
        }
    }

    public function configure()
    {
        $backCwd = getcwd();
        chdir($this->buildDir);
        $configureOptions = '';
        foreach ($this->options['enable'] as $name => $option) {
            if ($option->type == 'enable') {
                $decision = $option->input == true ? 'enable' : 'disable';
            } elseif ($option->type == 'disable') {
                $decision = $option->input == false ? 'enable' : 'disable';
            } else {
                throw new \Exception(
                    'Option ' . $name . ' is not well-formed; ' .
                    'its type must be “enable” or “disable”, got ' .
                    $option->type
                );
            }

            $configureOptions .= ' --' . $decision . '-' . $name;
        }
        $opt = $this->pkg->getConfigureOptions();
        $extEnableOption = $opt['enable'][$this->pkg->getName()];
        if ($extEnableOption->type == 'enable') {
            $confOption = '--enable-' . $this->pkg->getName() . '=shared';
        } else {
            $confOption = '--with-' . $this->pkg->getName() . '=shared';
        }
        $configureOptions = $confOption . ' ' . $configureOptions;

        $res = $this->runCommand($this->pkg->getRootDir() . '/configure '. $configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '. $this->buildDir . '\config.log');
        }
    }

    public function build()
    {
        $backCwd = getcwd();
        chdir($this->buildDir);
        $this->runCommand('make');
        chdir($backCwd);
        if (!$this->runCommand('make')) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
        $backCwd = getcwd();
        chdir($this->buildDir);
        $this->runCommand('make install');
        chdir($backCwd);
    }

    /**
     * @param string $command
     *
     * @return integer
     *
     * @throws \Exception
     */
    private function runCommand($command, $callback = null)
    {
        $this->log(1, 'running: ' . $command);
        $pp = popen("$command 2>&1", 'r');
        if (!$pp) {
            throw new \Exception(
                'Failed to run the following command: ' . $command
            );
        }

        if ($callback && $callback[0]->debug == 1) {
            $oldDbg = $callback[0]->debug;
            $callback[0]->debug = 2;
        }

        while ($line = fgets($pp, 1024)) {
            if ($callback) {
                call_user_func($callback, 'cmdoutput', $line);
            } else {
                $this->log(2, rtrim($line));
            }
        }

        if ($callback && isset($oldDbg)) {
            $callback[0]->debug = $oldDbg;
        }

        $exitCode = is_resource($pp) ? pclose($pp) : -1;

        return ($exitCode == 0);
    }
}
