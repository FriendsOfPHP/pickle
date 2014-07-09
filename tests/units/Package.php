<?php
namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class Package extends atoum
{
    public function test__construct()
    {
        $nameRegex = $this->realdom->regex('/\w+/');
        $versionRegex = $this->realdom->regex('/\d+(\.\d+){3}/');
        $prettyVersionRegex = $this->realdom->regex('/\d+(\.\d+){2}/');

        $this
            ->given(
                $name = $this->sample($nameRegex),
                $version = $this->sample($versionRegex),
                $prettyVersion = $this->sample($prettyVersionRegex)
            )
            ->if($this->newTestedInstance($name, $version, $prettyVersion))
            ->then
                ->string($this->testedInstance->getPrettyName())->isEqualTo($name)
                ->string($this->testedInstance->getPrettyVersion())->isEqualTo($prettyVersion)
                ->string($this->testedInstance->getVersion())->isEqualTo($version)
                ->string($this->testedInstance->getStability())->isEqualTo('stable')
                ->boolean($this->testedInstance->isDev())->isFalse
        ;
    }
}
