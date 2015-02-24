<?php

namespace Pickle\Base\Interfaces;

interface Engine
{
    public function __construct($phpCli = PHP_BINARY);
    public function getName();
    public function getArchitecture();
    public function getCompiler();
    public function getPath();
    public function getVersion();
    public function getMajorVersion();
    public function getMinorVersion();
    public function getReleaseVersion();
    public function getZts();
    public function getExtensionDir();
    public function getIniPath();
}

