<?php

namespace Pickle\tests\units\Engine;

use atoum;
use Pickle\tests;

class PHP extends atoum
{
    public function beforeTestMethod($method)
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR') === false) {
            $this->skip('Can only run on Windows');
        }
    }

    public function test__construct_bad()
    {
        $this->assert
            ->exception(function () {
                new \Pickle\Engine\PHP("");
            });

        $this->assert
            ->exception(function () {
                new \Pickle\Engine\PHP("c:\\windows\\system32\\at.exe");
            });
    }

    public function test__construct_ok()
    {
        $p = new \Pickle\Engine\PHP();

        $this
            ->object($p)
                ->isInstanceOf('\Pickle\Engine\PHP');
    }

    public function testgetFromConstants_ok()
    {
        $p = new \Pickle\Engine\PHP();

        $this
            ->object($p)
                ->isInstanceOf('\Pickle\Engine\PHP');

        $this
            ->string($p->getArchitecture())
                ->match(",x\d{2},");

        $this
            ->string($p->getCompiler())
                ->match("/vc\d{1,2}/");

        $this
            ->boolean(file_exists($p->getPath()))
                ->isTrue()
            ->boolean(is_executable($p->getPath()))
                ->isTrue();

        $this
            ->string($p->getMajorVersion())
                ->match(",\d,");

        $this
            ->string($p->getMinorVersion())
                ->match(",\d,");

        $this
            ->string($p->getReleaseVersion())
                ->match("/\d{1,2}/");

        $this
            ->boolean($p->getZts());

        $this
            ->boolean(file_exists($p->getExtensionDir()))
                ->isTrue();

        $this
            ->boolean(file_exists($p->getIniPath()))
                ->isTrue()
            ->boolean(is_dir($p->getIniPath()) || is_file($p->getIniPath()))
                ->isTrue();
    }
}
