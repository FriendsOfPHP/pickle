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

namespace Pickle\Package\PHP\Util\XML;

use Composer\Package\Loader\LoaderInterface;
use Pickle\Package\PHP;
use Pickle\Package\Util\Header;

class Loader
{
    protected $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param string $path
     * @throws \InvalidArgumentException|\RuntimeException|\Exception
     * @return Pickle\Base\Interfaces\Package
     */
    public function load($path)
    {
        if (false === is_file($path)) {
            throw new \InvalidArgumentException('File not found: '.$path);
        }

        $xml = @simplexml_load_file($path);

        if (false === $xml) {
            $error = error_get_last();
            $exception = null;

            if (null !== $error) {
                $exception = new \Exception($error['message'], $error['type']);
            }

            throw new \RuntimeException('Failed to read '.$path, 0, $exception);
        }

        $this->validate($xml);

        $package = [
            'name' => (string) $xml->name,
            'version' => (string) $xml->version->release,
            'stability' => (string) $xml->stability->release,
            'description' => (string) $xml->summary,
        ];

        if (!isset($xml->providesextension)) {
            throw new \Exception('not a PHP extension package.xml, providesextension tag missing');
        }

        $authors = array();
        foreach (array($xml->lead, $xml->developer, $xml->contributor, $xml->helper) as $devs) {
            foreach($devs as $dev) {
                $authors[] = $dev;
            }
        }

        if (false === empty($authors)) {
            $package['authors'] = [];

            foreach ($authors as $author) {
                $package['authors'][] = [
                    'name' => (string) $author->name,
                    'email' => (string) $author->email,
                ];
            }
        }

        $opts = $configureOptions = [];

        if (isset($xml->extsrcrelease->configureoption)) {
            $opts = $xml->extsrcrelease->configureoption;
        }

        foreach ($opts as $opt) {
            $name = trim($opt['name']);
            $default = trim($opt['default']);
            $prompt = trim($opt['prompt']);

            $configureOptions[$name] = [
                'default' => $default,
                'prompt' => $prompt,
            ];
        }

        if (false === empty($configureOptions)) {
            $package['extra'] = ['configure-options' => $configureOptions];
        }

        if (isset($xml->license)) {
            $package['license'] = (string) $xml->license;
        }
        $package['type'] = 'extension';

        $ret_pkg = $this->loader->load($package);
	if (!$ret_pkg) {
		throw new \Exception("Package from '$path' failed to load.");
	}
        $ret_pkg->setRootDir(dirname($path));

        $src_ver = new Header\Version($ret_pkg);
        if ($src_ver != $ret_pkg->getPrettyVersion()) {
            throw new \Exception("Version mismatch - '".$src_ver."' != '".$ret_pkg->getPrettyVersion()."' in source vs. XML");
        }
        $ret_pkg->setType('extension');

        return $ret_pkg;
    }

    protected function validate(\SimpleXMLElement $xml)
    {
        if (-1 === version_compare($xml['version'], '2.0')) {
            throw new \RuntimeException('Unsupported package.xml version, 2.0 or later only is supported');
        }

        if (!isset($xml->providesextension)) {
            throw new \RuntimeException('Only extension packages are supported');
        }
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
