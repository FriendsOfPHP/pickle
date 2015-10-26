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

namespace Pickle\tests\units\Engine\PHP;

use atoum;
use Pickle\tests;

class Ini extends atoum
{
    protected function getEngineMock($path)
    {
        $this->mockGenerator->shuntParentClassCalls();

        $php =  new \mock\Pickle\Engine\PHP();

        $this->calling($php)->__construct = function ($dummy) {};
        $this->calling($php)->getIniPath = function () use ($path) {
            return $path;
        };

        $this->mockGenerator->unshuntParentClassCalls();

        return $php;
    }

    public function test__construct()
    {
        $php = $this->getEngineMock("");
        $this->assert
                ->exception(function () use ($php) {
                        new \Pickle\Engine\PHP\Ini($php);
                    });

        $php = $this->getEngineMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");
        $this
            ->object(new \Pickle\Engine\PHP\Ini($php))
                ->isInstanceOf("\Pickle\Engine\PHP\Ini");
    }

    public function testupdatePickleSection_empty()
    {
        /* empty file */
        $f = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty";
        $this
            ->string(file_get_contents($f))
                ->isEmpty();
        $this->do_testupdatePickleSection($f);
    }

    public function testupdatePickleSection_nofooter()
    {
        /* missing pickle section footer*/
        $f = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.only.sect.begin";
        $this->do_testupdatePickleSection($f);
    }

    public function testupdatePickleSection_simple()
    {
        /* simple file with correct pickle section */
        $f = FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.simple";
        $this->do_testupdatePickleSection($f);
    }

    protected function do_testupdatePickleSection($orig)
    {
        $fl = "$orig.test";
        $fl_exp = "$orig.exp";
        copy($orig, $fl);

        $php = $this->getEngineMock($fl);

        $ini = new \Pickle\Engine\PHP\Ini($php);
        $ini->updatePickleSection(array("php_pumpkin.dll", "php_hello.dll"));

        $this
            ->string(file_get_contents($fl))
                ->isEqualToContentsOfFile($fl_exp);

        unlink($fl);
    }

    public function testrebuildPickleParts_0()
    {
        $php = $this->getEngineMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");

        $in  = "extension=php_a.dll\n\nextension=php_b.dll\nextension=php_c.dll\n;";
        $exp = "extension=php_a.dll\nextension=php_b.dll";

        $this
            ->if($ini = new \Pickle\Engine\PHP\Ini($php))
            ->then
                ->string(
                    $this->invoke($ini)->rebuildPickleParts($in, array("php_c.dll"))
                )->isEqualTo($exp);
    }

    public function testrebuildPickleParts_1()
    {
        $php = $this->getEngineMock(FIXTURES_DIR . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . "php.ini.empty");

        $in  = "extension=php_a.dll\n;\n;\n\nextension=php_b.dll\nextension=php_c.dll";
        $exp = "extension=php_a.dll\nextension=php_c.dll";

        $this
            ->if($ini = new \Pickle\Engine\PHP\Ini($php))
            ->then
                ->string(
                    $this->invoke($ini)->rebuildPickleParts($in, array("php_b.dll"))
                )->isEqualTo($exp);
    }
}
