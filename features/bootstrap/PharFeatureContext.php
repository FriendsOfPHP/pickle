<?php

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class PharFeatureContext extends FeatureContext
{
    const PICKLE_BIN = 'pickle.phar';
}
