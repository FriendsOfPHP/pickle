<?php
namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class Package extends atoum
{
    public function test__construct()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->string($this->testedInstance->getRootDir())->isEqualTo(realpath($path))
            ->given($path = uniqid())
            ->then
                ->exception(function() use ($path) {
                    $this->newTestedInstance($path);
                })
                    ->isInstanceOf('InvalidArgumentException')
                    ->hasMessage('Directory not found: ' . $path)
            ->given($path = FIXTURES_DIR . '/package-pre-2.0')
            ->then
                ->exception(function() use ($path) {
                    $this->newTestedInstance($path);
                })
                    ->isInstanceOf('InvalidArgumentException')
                    ->hasMessage('File not found: ' . $path . '/pickle.json')
        ;
    }

    public function testGetVersion()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->string($this->testedInstance->getVersion())->isEqualTo('3.1.15')
            ->given($path = FIXTURES_DIR . '/empty-package')
            ->if($this->newTestedInstance($path))
            ->then
                ->exception(function() {
                    $this->testedInstance->getVersion();
                })
                    ->isInstanceOf('RuntimeException')
                    ->hasMessage('Cannot find any RELEASE file')
        ;
    }

    public function testGetStatus()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->string($this->testedInstance->getStatus())->isEqualTo('beta')
            ->given($path = FIXTURES_DIR . '/empty-package')
            ->if($this->newTestedInstance($path))
            ->then
                ->exception(function() {
                    $this->testedInstance->getStatus();
                })
                    ->isInstanceOf('RuntimeException')
                    ->hasMessage('Cannot find any RELEASE file')
        ;
    }

    public function testGetAuthors()
    {
        $this
            ->given($path = FIXTURES_DIR . '/package')
            ->if($this->newTestedInstance($path))
            ->then
                ->array($this->testedInstance->getAuthors())->isEqualTo([
                    [
                        'name' => 'Rasmus Lerdorf',
                        'handle' => 'rasmus',
                        'email' => 'rasmus@php.net',
                        'active' => 'yes'
                    ],
                    [
                        'name' => 'Sara Golemon',
                        'handle' => 'pollita',
                        'email' => 'pollita@php.net',
                        'active' => 'no'
                    ]
                ])
            ->given($path = FIXTURES_DIR . '/empty-package')
            ->if($this->newTestedInstance($path))
            ->then
                ->exception(function() {
                    $this->testedInstance->getAuthors();
                })
                    ->isInstanceOf('RuntimeException')
                    ->hasMessage('Cannot find any CREDITS file')
        ;
    }
}
