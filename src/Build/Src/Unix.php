<?php

namespace Pickle\Build\Src;

use Pickle\Build\Src\Build;
use Pickle\Build\Src\AbstractBuild;

class Unix extends AbstractBuild implements Build
{
    public function prepare()
    {

    }

    public function phpize()
    {
        $backCwd = getcwd();
        chdir($this->pkg->getSourceDir());

        $res = $this->runCommand('phpize');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('phpize failed');
        }
    }

    protected function prepareConfigOpts()
    {
        $configureOptions = '';
        foreach ($this->options as $name => $option) {
            if ('enable' === $option->type) {
                true == $option->input ? 'enable' : 'disable';
            } elseif ('disable' == $option->type) {
                false == $option->input ? 'enable' : 'disable';
            } elseif ('with' === $option->type) {
                if ($option->input == 'yes' || $option->input == '1' || $option->type === true) {
                    $configureOptions .= ' --with-' . $name;
                } elseif ($option->input == 'no' || $option->input == '0' || $option->type === false) {
                    $configureOptions .= ' --without-' . $name;
                } else {
                     $configureOptions .= ' --with-' . $name. '=' . $option->input;
                }
            }
        }

        $this->appendPkgConfigureOptions($configureOptions);

        return $configureOptions;
    }

    public function configure($opts = NULL)
    {
        $backCwd = getcwd();
        chdir($this->tempDir);

        /* XXX check sanity */
        $configureOptions = $opts ? $opts : $this->prepareConfigOpts();

        $res = $this->runCommand($this->pkg->getSourceDir() . '/configure '. $configureOptions);
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('configure failed, see log at '. $this->tempDir . '\config.log');
        }
    }

    public function make()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('make');
        chdir($backCwd);

        if (!$res) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
        $backCwd = getcwd();
        chdir($this->tempDir);
        $res = $this->runCommand('make install');
        chdir($backCwd);
        if (!$res) {
            throw new \Exception('make install failed');
        }
    }
}

