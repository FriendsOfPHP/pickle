<?php
namespace Pickle\tests\units\Package\XML;

use atoum;
use Pickle\tests;

class Parser extends atoum
{
    public function testParse()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->variable($this->testedInstance->parse())
            ->given($path = uniqid())
            ->then
                ->exception(function() use ($path) {
                    $this->newTestedInstance($path);
                })
                    ->hasMessage('Directory not found: ' . $path)
            ->given($path = FIXTURES_DIR)
            ->then
                ->exception(function() use ($path) {
                    $this->newTestedInstance($path);
                })
                    ->hasMessage('File not found: ' . $path . '/package.xml')
            ->given($path = FIXTURES_DIR . '/package-no-extension')
            ->if($this->newTestedInstance($path))
            ->then
                ->output(function() {
                    $this->exception(function() {
                        $this->testedInstance->parse();
                    })
                        ->hasMessage('Only extension packages are supported');
                })
                    ->isEmpty
            ->given($path = FIXTURES_DIR . '/package-pre-2.0')
            ->if($this->newTestedInstance($path))
            ->then
                ->output(function() {
                    $this->exception(function() {
                        $this->testedInstance->parse();
                    })
                        ->hasMessage('Unsupported package.xml version, 2.0 or later only is supported');
                })
                    ->isEmpty
        ;
    }

    public function testParseXmlError()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $errorMessage = uniqid(),
                $errorCode = E_USER_NOTICE,
                $this->function->simplexml_load_file = false
            )
            ->if($this->newTestedInstance($path))
            ->then
                ->exception(function() {
                    $this->testedInstance->parse();
                })
                    ->hasMessage('Failed to read ' . $path . '/package.xml')
        ;
    }
}
