pickle - PHP Extension installer [![SensioLabsInsight](https://insight.sensiolabs.com/projects/7e153d04-79be-47e6-b2ee-60cdc2665dd5/small.png)](https://insight.sensiolabs.com/projects/7e153d04-79be-47e6-b2ee-60cdc2665dd5)
================================

Pickle installs PHP extensions easily on all platforms.

[![Code Climate](https://codeclimate.com/github/FriendsOfPHP/pickle.svg)](https://codeclimate.com/github/FriendsOfPHP/pickle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/?branch=master)
[![Build Status](https://travis-ci.org/FriendsOfPHP/pickle.svg?branch=master)](https://travis-ci.org/FriendsOfPHP/pickle)


Introduction
------------

Pickle is a new PHP extension installer. It is based on Composer and the plan is to get Composer to fully support it. See https://github.com/composer/composer/pull/2898#issuecomment-48439196 for the Composer part of the discussions.

Pickle fully supports existing extensions in http://pecl.php.net, running the following will install the latest available version of the memcache extension:

```sh
$ bin/pickle install memcache
```

Windows is fully supported, to install binaries or from the sources (work in progress and given that you have a working build environment in place).

The concept behind Pickle is to ease the life of both developers and end users.

For end users, nothing changes much except that Pickle is based on modern concepts and works with multiple protocols (git or http(s) URLs).

For developers, it drastically reduces the release work. Extension meta information is not duplicated anymore. Configuration options, files to package etc. are automatically fetched from the sources and the respective files are updated during the release process. There is no risk anymore of forgetting to update the version here or there, or to neglect to include a file.

Installation
------------

Clone this repository and install the dependencies with
[Composer](http://getcomposer.org/):

```sh
$ composer install
```

A [phar is also available](https://github.com/FriendsOfPHP/pickle/releases/download/v0.4.0/pickle.phar), but it might be outdated.

If you like to create your own phar from the pickle sources, you will need to install Box (http://box-project.github.io/box2/). Then clone the repository and run the following commands:

```sh
$ cd pickle
$ composer install --no-dev --optimize-autoloader
$ php -d phar.readonly=0 box.phar build
```

Usage
-----

Usage is pretty straightforward. For example, to install the memcache extension run the following command:

```sh
$ bin/pickle install memcache
```

You can also use pickle from your extension directory, the following command:

```sh
$ cd myext
$ bin/pickle install
```

A list of the commands is available using:

```sh
$ bin/pickle list
```

To get extended help for a given command, use:

```sh
$ bin/pickle help install
```

To convert a package (based on package.xml current PECL installer), use:

```sh
$ bin/pickle convert /home/pierre/myext/
```

Or run it from the extension source directory.

Contributing
------------

Fork the project, create a feature branch and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-1](http://www.php-fig.org/psr/psr-1/) and
[PSR-2](http://www.php-fig.org/psr/psr-2/) coding standards.

To avoid CS issues, you should use [php-cs-fixer](http://cs.sensiolabs.org/):

```sh
$ php-cs-fixer fix src/
```

Support
-------

Support is available via the [issue
tracker](https://github.com/FriendsOfPHP/pickle/issues) in the Github project page
or via [IRC, EFNet, channel `#pickle`](http://chat.efnet.org/).

Running tests
-------------

Unit tests are written using [atoum](https://github.com/atoum/atoum).
You will get atoum, among other dependencies, when running `composer install`.
To run tests, you will need to run the following command:

```sh
$ vendor/bin/atoum

# To run tests in a loop, ideal to do TDD
$ vendor/bin/atoum --loop
```

There are also some [Behat](https://github.com/behat/behat) tests.
You will get Behat, among other dependencies, when running `composer install`.
To run tests, you will need to run the following command:

```sh
$ vendor/bin/behat

# To choose the test suite you want to run
$ vendor/bin/behat -s pickle
```

Pickle is covered using 4 Behat tests suites:

* `pickle` runs tests against pickle's sources
* `pickle_phar` runs tests against pickle's Phar which you have to manually
  build
* `pecl` tests PECL extensions conversion with pickle's sources
* `phar_pecl` tests PECL extensions conversion with pickle's Phar
