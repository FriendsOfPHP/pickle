<?php
namespace Pickle\tests\units;

use atoum;
use Pickle\tests;

class Package extends atoum
{
    public function test__construct()
    {
        $this
            ->given(
                $name = uniqid(),
                $version = '1.0.0.0',
                $prettyVersion = '1.0.0'
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
