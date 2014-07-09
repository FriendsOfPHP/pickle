<?php
namespace Pickle;

class BuildSrcUnix
{
    use FileOps;

    private $pkg;
    private $options;
    private $log = '';
    private $cwdBack;
    private $tempDir;

    public function __construct(Package $pkg, $options = null)
    {
        $this->pkg = $pkg;
        $this->options = $options;
        $this->cwdBack = getcwd();
    }

    /**
     * @param integer $level
     * @param string  $msg
     */
    public function log($level, $msg)
    {
        $this->log .= $level . ': ' . $msg . "\n";
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
        chdir($this->tempDir);
        $configureOptions = '';
        foreach ($this->options['enable'] as $name => $option) {
            if ('enable' === $option->type) {
                $decision = true == $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                $decision = false == $option->input ? 'enable' : 'disable';
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
        $extEnableOption = $opt[$this->pkg->getName()];
        if ('enable' == $extEnableOption->type) {
            $confOption = '--enable-' . $this->pkg->getName() . '=shared';
        } else {
            $confOption = '--with-' . $this->pkg->getName() . '=shared';
        }
        $configureOptions = $confOption . ' ' . $configureOptions;

        $res = $this->runCommand($this->pkg->getRootDir() . '/configure '. $configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '. $this->tempDir . '\config.log');
        }
    }

    public function build()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $this->runCommand('make');
        chdir($backCwd);
        if (!$this->runCommand('make')) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $this->runCommand('make install');
        chdir($backCwd);
    }

    /**
     * @param string $command
     *
     * @return boolean
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

        if (1 == $callback && $callback[0]->debug) {
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

        return (0 === $exitCode);
    }
}
