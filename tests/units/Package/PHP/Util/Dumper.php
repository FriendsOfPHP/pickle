<?php
namespace Pickle\tests\units\Package\PHP\Util;

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
                    'email' => 'pollita@php.net'
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
