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

namespace Pickle\Package\HHVM\Util\Cmake;

use Composer\Package\Loader\LoaderInterface;

class Parser
{
    protected $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function load($path)
    {
        if (false === is_file($path)) {
            throw new \InvalidArgumentException('File not found: '.$path);
        }

        $cont = file_get_contents($path);

        /* Only the ext name seems to be readable from cmake yet,
        anything else left a dummy. Need some way to gather this
               info in the future, where ever it's provided. */
        $package = [
            'name' => $this->getExtName($cont),
            'version' => '0.0.0',
            'stability' => 'alpha',
            'description' => 'no description',
        ];

        return $this->loader->load($package);
    }

    public function getExtName($cont)
    {
        $ret = null;

        if (preg_match(",HHVM_EXTENSION\(([^\s]+)\s+,", $cont, $m)) {
            $ret = $m[1];
        }

        if (!$ret) {
            throw new \Exception("Couldn't parse extension name");
        }

        return $ret;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
