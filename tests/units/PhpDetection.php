<?php

namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class PhpDetection extends atoum
{
    public function beforeTestMethod($method)
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR') === false) {
            $this->skip('Cannot only run on Windows');
        }
    }

    public function test__construct_bad()
    {
        $this->assert
            ->exception(function () {
                new \Pickle\PhpDetection("");
            });

        $this->assert
            ->exception(function () {
                new \Pickle\PhpDetection("c:\\windows\\system32\\at.exe");
            });
    }

    public function test__construct_ok()
    {
        $p = new \Pickle\PhpDetection();

        $this
            ->object($p)
                ->isInstanceOf('\Pickle\PhpDetection');
    }

    public function testgetFromConstants_ok()
    {
        $p = new \Pickle\PhpDetection();

        $this
            ->object($p)
                ->isInstanceOf('\Pickle\PhpDetection');

        $this
            ->string($p->getArchitecture())
                ->match(",x\d{2},");

        $this
            ->string($p->getCompiler())
                ->match("/vc\d{1,2}/");

        $this
            ->boolean(file_exists($p->getPhpCliPath()))
                ->isTrue()
            ->boolean(is_executable($p->getPhpCliPath()))
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
            ->boolean(file_exists($p->getPhpIniDir()))
                ->isTrue()
            ->boolean(is_dir($p->getPhpIniDir()) || is_file($p->getPhpIniDir()))
                ->isTrue();
    }
}
