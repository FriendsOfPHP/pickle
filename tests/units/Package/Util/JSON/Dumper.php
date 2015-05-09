<?php
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
