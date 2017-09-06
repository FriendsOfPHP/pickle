<?php

/**
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

namespace Pickle\tests\units\Engine;

use atoum;

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
