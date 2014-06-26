pickle - PHP Extension installer
================================

Pickle installs PHP extensions easily on all platforms.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/?branch=master)
[![Build Status](https://travis-ci.org/FriendsOfPHP/pickle.svg?branch=master)](https://travis-ci.org/FriendsOfPHP/pickle)

Installation and usage
----------------------

Clone this repository and install dependencies with
[Composer](http://getcomposer.org/):

```sh
$ composer install
```

And then, run, in your extension directory, the following command:

```sh
$ bin/pickle validate
$ bin/pickle install
```

For any help, run:

```sh
$ bin/pickle -h
```

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
tracker](https://github.com/pierrejoye/pickle/issues) in the Github project page
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
