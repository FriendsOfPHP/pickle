<?php

/*
 * Pickle
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2015-2015, Pickle community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Pickle\tests\units;

use atoum;
use Pickle\Config as TestedClass;

class Config extends atoum
{
    public function test__construct()
    {
        $baseDir = null;
        $homeDir = null;
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

            ->given($this->function->getenv = function ($var) use (&$baseDir) {
                return $var === 'PICKLE_BASE_DIR' ? $baseDir = uniqid() : false;
            })
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

            ->given(
                $this->function->getenv = function ($var) use (&$baseDir, &$homeDir) {
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
