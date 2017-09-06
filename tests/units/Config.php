<?php

namespace Pickle\tests\units;

use atoum;
use Pickle\Config as TestedClass;

class Config extends atoum
{
    public function test__construct()
    {
        $this
            ->given(
                $this->function->getenv = false,
                $this->function->sys_get_temp_dir = $tmpDir = 'tmp'
            )
            ->when($this->newTestedInstance(false))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($tmpDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->never
            ->when($this->newTestedInstance(true))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($tmpDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->once

            ->given($this->function->getenv = function ($var) use (&$baseDir) { return $var === 'PICKLE_BASE_DIR' ? $baseDir = uniqid() : false; })
            ->when($this->newTestedInstance(false))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($tmpDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->once
            ->when($this->newTestedInstance(true))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($baseDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->twice

            ->given($this->function->getenv = function ($var) use (&$baseDir, &$homeDir) {
                return $var === 'PICKLE_BASE_DIR' ? $baseDir = uniqid() : $homeDir = uniqid();
            }
            )
            ->when($this->newTestedInstance(false))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($homeDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->twice
            ->when($this->newTestedInstance(true))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($baseDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->thrice

            ->when($this->newTestedInstance(false, $baseDir = uniqid()))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($baseDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->thrice

            ->when($this->newTestedInstance(true, $baseDir = uniqid()))
            ->then
                ->string($this->testedInstance->get('vendor-dir'))->isEqualTo($this->makePath($baseDir, TestedClass::$defaultConfig['vendor-dir']))
                ->function('getenv')
                    ->wasCalledWithArguments('PICKLE_BASE_DIR')->thrice
        ;
    }

    private function makePath($head, $tail)
    {
        return $head . DIRECTORY_SEPARATOR . TestedClass::DEFAULT_BASE_DIRNAME . DIRECTORY_SEPARATOR . $tail;
    }
}
