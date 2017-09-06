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

namespace Pickle\tests\units\Package\Util;

use atoum;

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
                ->array($this->testedInstance->dump($package))
                    ->string['name']->isEqualTo($name)
                    ->string['version']->isEqualTo($version)
                    ->string['type']->isEqualTo('extension')
                    ->notHasKeys([
                        'license',
                        'authors',
                        'description',
                        'support',
                        'extra',
                    ])
            ->given(
                $license = uniqid(),
                $this->calling($package)->getLicense = $license
            )
            ->then
                ->array($this->testedInstance->dump($package))
                    ->string['license']->isEqualTo($license)
            ->given(
                $authors = [
                    'name' => 'Rasmus Lerdorf',
                    'email' => 'rasmus@php.net',
                ],
                [
                    'name' => 'Sara Golemon',
                    'email' => 'pollita@php.net',
                ],
                $this->calling($package)->getAuthors = $authors
            )
            ->then
                ->array($this->testedInstance->dump($package))
                ->array['authors']->isEqualTo($authors)
            ->given(
                $description = uniqid(),
                $this->calling($package)->getDescription = $description
            )
            ->then
                ->array($this->testedInstance->dump($package))
                ->string['description']->isEqualTo($description)
            ->given(
                $support = [
                    'email' => uniqid(),
                ],
                $extra = [
                    'configure-options' => [],
                ],
                $this->calling($package)->getSupport = $support,
                $this->calling($package)->getExtra = $extra
            )
            ->then
                ->array($this->testedInstance->dump($package))
                ->array['support']->isEqualTo($support)
                ->array['extra']->isEqualTo($extra)
        ;
    }
}
