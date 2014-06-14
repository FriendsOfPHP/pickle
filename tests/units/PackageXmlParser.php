<?php
namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class PackageXmlParser extends atoum
{
    public function testParse()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->output(function() {
                    $this->object($this->testedInstance->parse())->isInstanceOf('SimpleXmlElement');
                })
                    ->isEqualTo(
                        'Packager Version: 1.4.7' . PHP_EOL .
                        'XML Version: 2.0' . PHP_EOL .
                        'Extension pkg: dummy' . PHP_EOL .
                        'Package name: dummy' . PHP_EOL .
                        'Package version: 3.1.15' . PHP_EOL
                    )
            ->given($this->function->getcwd = $path)
            ->if($this->newTestedInstance)
            ->then
                ->output(function() {
                    $this->object($this->testedInstance->parse())->isInstanceOf('SimpleXmlElement');
                })
                    ->isEqualTo(
                        'Packager Version: 1.4.7' . PHP_EOL .
                        'XML Version: 2.0' . PHP_EOL .
                        'Extension pkg: dummy' . PHP_EOL .
                        'Package name: dummy' . PHP_EOL .
                        'Package version: 3.1.15' . PHP_EOL
                    )
            ->given($path = FIXTURES_DIR . '/package-pre-2.0')
            ->if($this->newTestedInstance($path))
            ->then
                ->output(function() {
                    $this->exception(function() {
                        $this->testedInstance->parse();
                    })
                        ->hasMessage('Unsupported package.xml version, 2.0 or later only is supported');
                })
                    ->isEmpty()
            ->given($path = FIXTURES_DIR . '/package-no-extension')
            ->if($this->newTestedInstance($path))
            ->then
                ->output(function() {
                    $this->exception(function() {
                        $this->testedInstance->parse();
                    })
                        ->hasMessage('Only extension packages are supported');
                })
                    ->isEmpty()
        ;
    }
}
