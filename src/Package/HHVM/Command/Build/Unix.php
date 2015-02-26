<?php

namespace Pickle\Package\HHVM\Command\Build;

use Pickle\Base\Abstracts;
use Pickle\Base\Interfaces;

class Unix extends Abstracts\Package\Build implements Interfaces\Package\Build
{
    public function prepare()
    {
	    $this->hphpize();
    }

    public function hphpize()
    {
	$newcwd = $this->pkg->getSourceDir();

        $res = $this->runCommand("cd $newcwd && hphpize");
        if (!$res) {
            throw new \Exception('hphpize failed');
        }
    }

    public function configure($opts = null)
    {
	$newcwd = $this->pkg->getSourceDir();

        $res = $this->runCommand("cd $newcwd && cmake .");
        if (!$res) {
            throw new \Exception('cmake failed');
        }
    }

    public function make()
    {
	$newcwd = $this->pkg->getSourceDir();

        $res = $this->runCommand("cd $newcwd && make");
        if (!$res) {
            throw new \Exception('make failed');
        }
    }

    public function install()
    {
	$newcwd = $this->pkg->getSourceDir();
        $res = $this->runCommand("cd $newcwd && make install");
        if (!$res) {
            throw new \Exception('make install failed');
        }

	$this->updateIni();
    }

    protected function updateIni()
    {
        $ini = \Pickle\Engine\Ini::factory(\Pickle\Engine::factory());
        $ini->updatePickleSection(array($this->pkg->getName()));
    }
}
