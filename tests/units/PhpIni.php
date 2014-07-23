<?php

namespace Pickle\tests\units;
//namespace Pickle\tests\mocks;

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

    public function testupdatePickleSection_empty()
    {
        $orig = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty";
        $fl = "$orig.test";
        copy($orig, $fl);
        $this
            ->string(file_get_contents($fl))
                ->isEmpty();


        $php = $this->getPhpDetectionMock($fl);

        $ini = new \Pickle\PhpIni($php);
        $ini->updatePickleSection(array("a.dll", "b.dll"));

        $result = trim(preg_replace(",\n+,", "\n", file_get_contents($fl)));
        $expect = ";Pickle installed extension, do not edit this line and below\n" . 
                  "extension=a.dll\n" .
                  "extension=b.dll\n" .
                  ";Pickle installed extension, do not edit this line and above";
        unlink($fl);

        $this
            ->string($result)
                ->isEqualTo($expect);

    }
    
}
