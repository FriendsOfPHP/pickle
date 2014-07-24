<?php

namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class PhpIni extends atoum
{
    protected function getPhpDetectionMock($path)
    {
        $php =  new \mock\Pickle\PhpDetection;

        $this->calling($php)->getPhpIniDir = function() use ($path) {
            return $path;
        };

        return $php;
    }

    public function test__construct()
    {
        $php = $this->getPhpDetectionMock("");
        $this->assert
                ->exception(function() use($php) {
                        new \Pickle\PhpIni($php);
                    });

        $php = $this->getPhpDetectionMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");
        $this
            ->object(new \Pickle\PhpIni($php))
                ->isInstanceOf("\Pickle\PhpIni");
    }

    public function testupdatePickleSection()
    {
        $files = array(
            FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty",
            FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.only.sect.begin",
            FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.simple",
        );

        foreach ($files as $f) {
            $this->do_testupdatePickleSection($f);
        }
    }

    protected function do_testupdatePickleSection($orig)
    {
        $fl = "$orig.test";
        $fl_exp = "$orig.exp";
        copy($orig, $fl);
        $this
            ->string(file_get_contents($fl))
                ->isEmpty();


        $php = $this->getPhpDetectionMock($fl);

        $ini = new \Pickle\PhpIni($php);
        $ini->updatePickleSection(array("php_pumpkin.dll", "php_hello.dll"));

        $result = file_get_contents($fl);
        $expect = file_get_contents($fl_exp);
        unlink($fl);

        $this
            ->string($result)
                ->isEqualTo($expect);

    }
    
}
