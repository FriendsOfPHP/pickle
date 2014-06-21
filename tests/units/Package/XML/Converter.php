<?php
namespace Pickle\tests\units\Package\XML;

use atoum;
use mageekguy\atoum\mock\streams\fs\directory;
use Pickle\tests;

class Converter extends atoum
{
    public function testMaintainers()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $this->function->file_put_contents->doesNothing(),
                $parser = new \mock\Pickle\Package\XML\Parser($path),
                $this->calling($parser)->getAuthors = [
                    (object) [
                        'name' => 'Rasmus Lerdorf',
                        'user' => 'rasmus',
                        'email' => 'rasmus@php.net',
                        'active' => 'yes'
                    ],
                    (object) [
                        'name' => 'Pierre Joye',
                        'user' => 'pierre',
                        'email' => 'pierre@php.net',
                        'active' => 'yes'
                    ]
                ]
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->maintainers();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments(
                        $path . '/CREDITS',
                        "Rasmus Lerdorf (rasmus) (rasmus@php.net) (yes)\n" .
                        "Pierre Joye (pierre) (pierre@php.net) (yes)\n"
                    )->once()
        ;
    }

    public function testSummary()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $this->function->file_put_contents->doesNothing(),
                $parser = new \mock\Pickle\Package\XML\Parser($path),
                $this->calling($parser)->getSummary = $summary = uniqid(),
                $this->calling($parser)->getDescription = $description = uniqid()
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->summary();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments($path . '/README', $summary . "\n\n" . $description)->once()
        ;
    }

    public function testRelease()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $this->function->file_put_contents->doesNothing(),
                $parser = new \mock\Pickle\Package\XML\Parser($path),
                $this->calling($parser)->getCurrentRelease = [
                    'date' => '2014-06-14',
                    'version' => '3.1.15',
                    'status' => 'beta',
                    'api' => [
                        'version' => '3.1.0',
                        'status' => 'stable'
                    ],
                    'notes' => 'This is a note'
                ]
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->release();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments(
                        $path . '/RELEASE-3.1.15',
                        "Date:             2014-06-14\n" .
                        "Package version:  3.1.15\n" .
                        "Package state:    beta\n" .
                        "API Version:      3.1.0\n" .
                        "API state:        stable\n\n" .
                        "Changelog:\n" .
                        "This is a note\n"
                    )->once()
        ;
    }

    public function testChangelog()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $this->function->file_put_contents->doesNothing(),
                $parser = new \mock\Pickle\Package\XML\Parser($path),
                $this->calling($parser)->getPastReleases = [
                    [
                        'date' => '2013-01-02',
                        'version' => '3.1.14',
                        'status' => 'beta',
                        'api' => [
                            'version' => '3.1.0',
                            'status' => 'stable'
                        ],
                        'notes' => 'This is a note'
                    ],
                    [
                        'date' => '2003-07-01',
                        'version' => '2.0.0',
                        'status' => 'stable',
                        'api' => [
                            'version' => '2.0.0',
                            'status' => 'stable'
                        ],
                        'notes' => 'This is an old note'
                    ]
                ]
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->changelog();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments(
                        $path . '/RELEASE-3.1.14',
                        "Date:             2013-01-02\n" .
                        "Package version:  3.1.14\n" .
                        "Package state:    beta\n" .
                        "API Version:      3.1.0\n" .
                        "API state:        stable\n\n" .
                        "Changelog:\n" .
                        "This is a note\n"
                    )->once()
                    ->wasCalledWithArguments(
                        $path . '/RELEASE-2.0.0',
                        "Date:             2003-07-01\n" .
                        "Package version:  2.0.0\n" .
                        "Package state:    stable\n" .
                        "API Version:      2.0.0\n" .
                        "API state:        stable\n\n" .
                        "Changelog:\n" .
                        "This is an old note\n"
                    )->once()
        ;
    }

    public function testLicense()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $this->function->file_put_contents->doesNothing(),
                $parser = new \mock\Pickle\Package\XML\Parser($path),
                $this->calling($parser)->getCurrentRelease = [
                    'license' => 'PHP License'
                ]
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->license();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments($path . '/LICENSE', "This package is under the following license(s):\nPHP License")->once()
        ;
    }

    public function testGenerateJson()
    {
        $this
            ->given(
                $path = FIXTURES_DIR . '/package',
                $this->function->file_put_contents->doesNothing(),
                $parser = new \mock\Pickle\Package\XML\Parser($path),
                $this->calling($parser)->getName = $name = uniqid()
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->generateJson();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments(
                        $path . '/pickle.json',
                        json_encode(
                            [
                                'name' => $name,
                                'type' => 'extension',
                                'extra' => [
                                    'configure-options' => []
                                ]
                            ],
                            JSON_PRETTY_PRINT
                        )
                    )->once()
            ->given(
                $this->calling($parser)->getExtraOptions = $options = [
                    [
                        'prompt' => 'an option',
                        'default' => 'yes'
                    ],
                    [
                        'prompt' => 'another option',
                        'default' => 'no'
                    ]
                ]
            )
            ->if($this->newTestedInstance($path, $parser))
            ->when(function() {
                $this->testedInstance->generateJson();
            })
                ->function('file_put_contents')
                    ->wasCalledWithArguments(
                        $path . '/pickle.json',
                        json_encode(
                            [
                                'name' => $name,
                                'type' => 'extension',
                                'extra' => [
                                    'configure-options' => $options
                                ]
                            ],
                            JSON_PRETTY_PRINT
                        )
                    )->once()
        ;
    }
}
