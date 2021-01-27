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

namespace Pickle\Package\Util\Header;

use Composer\Package\Version\VersionParser;
use Exception;
use Pickle\Base\Interfaces;
use Pickle\Package\Util;

class Version
{
    protected $package;

    protected $header;

    protected $version;

    protected $macroNameRegex;

    public function __construct(Interfaces\Package $package)
    {
        $this->package = $package;
        $this->macroNameRegex = '(?:PHP_)?' . strtoupper($this->package->getSimpleName()) . '_(?:EXT_)?VERSION';

        $this->version = $this->getVersionFromHeader();
    }

    public function __toString()
    {
        return $this->version;
    }

    public function fileHasVersionMacro($fname)
    {
        return $this->getVersionFromHeaderFile($fname) !== null;
    }

    public function getVersionFromHeader()
    {
        $headers = self::rglob($this->package->getSourceDir() . DIRECTORY_SEPARATOR . '*.h');
        foreach ($headers as $header) {
            $macroValue = $this->getVersionFromHeaderFile($header);
            if ($macroValue !== null) {
                return $macroValue;
            }
        }
        throw new Exception("Couldn't parse or find the version defined in the {$this->macroNameRegex} macro");
    }

    public function updateJSON()
    {
        if ($this->package->getPrettyVersion() == $this->version) {
            /* Don't touch, it's the same. */
            return;
        }

        $dumper = new Util\Dumper();
        $composer_json = $this->package->getRootDir() . DIRECTORY_SEPARATOR . 'composer.json';

        $this->package->replaceVersion((new VersionParser())->normalize($this->version), $this->version);

        $len = file_put_contents($composer_json, json_encode($dumper->dump($this->package), JSON_PRETTY_PRINT));

        if (!$len) {
            throw new Exception("Failed to update '{$composer_json}'");
        }
    }

    /**
     * Extract the macro value from a header file.
     *
     * @throws Exception if we couldn't read the file
     *
     * @return string|null return NULL if the macro couldn't be found, the macro value otherwise
     */
    protected function getVersionFromHeaderFile(string $headerFilePath): ?string
    {
        $headerFileContents = file_get_contents($headerFilePath);
        if ($headerFileContents === false) {
            throw new Exception("Could not read header file {$headerFilePath}");
        }

        return $this->getVersionFromHeaderFileContents($headerFileContents);
    }

    /**
     * Extract the macro value from the contents of a header file.
     *
     * @return string|null return NULL if the macro couldn't be found, the macro value otherwise
     */
    protected function getVersionFromHeaderFileContents(string $headerFileContents): ?string
    {
        // Match versions surrounded by quotes and versions without quotes
        $versionMatcher = '(".*"|.*\b)';
        $pat = ',define\s+' . $this->macroNameRegex . '\s+' . $versionMatcher . ',i';
        $result = null;

        return preg_match($pat, $headerFileContents, $result) ? trim($result[1], '"') : null;
    }

    private function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, self::rglob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
