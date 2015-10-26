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
namespace Pickle\tests\units\Package\Util\JSON;

use atoum;
use Pickle\tests;

class Dumper extends atoum
{
    public function testDump()
    {
        $this
            ->given(
                $name = uniqid(),
                $version = '1.0.0',
                $type = 'extension',
                $package = new \mock\Pickle\Package\PHP\Package($name, $version, $version),
                $this->calling($package)->getPrettyName = $name,
                $this->calling($package)->getVersion = $version,
                $this->calling($package)->getType = $type
            )
            ->if($this->newTestedInstance)
            ->then
                ->string($this->testedInstance->dump($package))->isEqualTo(json_encode([
                    'name' => $name,
                    'version' => $version,
                    'type' => $type,
                ], JSON_PRETTY_PRINT))
            ->given(
                $license = uniqid(),
                $authors = [
                    'name' => 'Rasmus Lerdorf',
                    'email' => 'rasmus@php.net',
                ],
                [
                    'name' => 'Sara Golemon',
                    'email' => 'pollita@php.net'
                ],
                $description = uniqid(),
                $support = [
                    'email' => uniqid(),
                ],
                $extra = [
                    'configure-options' => [],
                ],
                $this->calling($package)->getLicense = $license,
                $this->calling($package)->getAuthors = $authors,
                $this->calling($package)->getDescription = $description,
                $this->calling($package)->getSupport = $support,
                $this->calling($package)->getExtra = $extra
            )
            ->then
                ->string($this->testedInstance->dump($package))->isEqualTo(json_encode([
                    'name' => $name,
                    'version' => $version,
                    'type' => $type,
                    'license' => $license,
                    'authors' => $authors,
                    'description' => $description,
                    'support' => $support,
                    'extra' => $extra,
                ], JSON_PRETTY_PRINT))
        ;
    }

    public function testDumpToFile()
    {
        $this
            ->given(
                $path = uniqid(),
                $name = uniqid(),
                $version = '1.0.0',
                $type = 'extension',
                $package = new \mock\Pickle\Package\PHP\Package($name, $version, $version),
                $this->calling($package)->getPrettyName = $name,
                $this->calling($package)->getVersion = $version,
                $this->calling($package)->getType = $type,
                $this->function->file_put_contents->doesNothing
            )
            ->if($this->newTestedInstance)
            ->when(function () use ($package, $path) {
                $this->testedInstance->dumpToFile($package, $path);
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments($path, json_encode([
                        'name' => $name,
                        'version' => $version,
                        'type' => $type,
                    ], JSON_PRETTY_PRINT))->once
        ;
    }
}
