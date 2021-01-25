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

namespace Pickle\tests\units\Package\PHP\Util\XML;

use atoum;

class Loader extends atoum
{
    public function testParse()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package/package.xml',
                $loader = new \mock\Composer\Package\Loader\LoaderInterface()
            )
            ->if($this->newTestedInstance($loader))
            ->then
                ->exception(function () use ($path) {
                    $this->testedInstance->load($path);
                })
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
