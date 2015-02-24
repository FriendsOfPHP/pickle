<?php
namespace Pickle\tests\units\Package\PHP\Util\XML;

use atoum;
use Pickle\tests;

class Loader extends atoum
{
    public function testParse()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package/package.xml',
                $loader = new \mock\Composer\Package\Loader\LoaderInterface(),
                $this->calling($loader)->load = $package = new \mock\Composer\Package\PackageInterface()
            )
            ->if($this->newTestedInstance($loader))
            ->then
                ->object($this->testedInstance->load($path))->isIdenticalTo($package)
            ->given($path = uniqid())
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
                    ->hasMessage('File not found: ' . $path)
            ->given($path = FIXTURES_DIR . '/package-no-extension/package.xml')
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
                    ->hasMessage('Only extension packages are supported')
            ->given($path = FIXTURES_DIR . '/package-pre-2.0/package.xml')
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
                    ->hasMessage('Unsupported package.xml version, 2.0 or later only is supported')
        ;
    }

    public function testParseXmlError()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package/package.xml',
                $loader = new \mock\Composer\Package\Loader\LoaderInterface(),
                $this->function->simplexml_load_file = false
            )
            ->if($this->newTestedInstance($loader))
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
                    ->hasMessage('Failed to read ' . $path)
        ;
    }
}
