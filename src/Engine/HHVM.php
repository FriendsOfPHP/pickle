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

namespace Pickle\Engine;

use Pickle\Base\Interfaces;
use Pickle\Base\Abstracts;

class HHVM extends Abstracts\Engine implements Interfaces\Engine
{
    public function __construct($phpCli = PHP_BINARY)
    {
    }

    public function hasSdk()
    {
        return false;
    }

    public function getName()
    {
        return 'hhvm';
    }

    public function getCompiler()
    {
        return '';
    }

    public function getPath()
    {
        return PHP_BINARY;
    }

    public function getVersion()
    {
        return HHVM_VERSION;
    }

    protected function getParsedVersion($type)
    {
        if ($type < 1 || $type > 2) {
            throw new \Exception('Invalid version info requested');
        }

        if (!preg_match(",(\d*)\.(\d*)\.(\d*),", HHVM_VERSION, $m)) {
            throw new \Exception("Couldn't parse HHVM_VERSION");
        }

        return isset($m[$type + 1]) ? $m[$type + 1] : 0;
    }

    public function getMajorVersion()
    {
        return $this->getParsedVersion(0);
    }

    public function getMinorVersion()
    {
        return $this->getParsedVersion(1);
    }

    public function getReleaseVersion()
    {
        return $this->getParsedVersion(2);
    }

    public function getZts()
    {
        return true;
    }

    public function getExtensionDir()
    {
        return ini_get('extension_dir');
    }

    public function getIniPath()
    {
        $ini = php_ini_loaded_file();

        if (!$ini && file_exists('/etc/hhvm/php.ini')) {
            $ini = '/etc/hhvm/php.ini';
        }

        return $ini;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
