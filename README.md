pickle - PHP Extension installer [![SensioLabsInsight](https://insight.sensiolabs.com/projects/7e153d04-79be-47e6-b2ee-60cdc2665dd5/small.png)](https://insight.sensiolabs.com/projects/7e153d04-79be-47e6-b2ee-60cdc2665dd5)
================================

Pickle installs PHP extensions easily on all platforms.

[![Code Climate](https://codeclimate.com/github/FriendsOfPHP/pickle.png)](https://codeclimate.com/github/FriendsOfPHP/pickle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfPHP/pickle/?branch=master)
[![Build Status](https://travis-ci.org/FriendsOfPHP/pickle.svg?branch=master)](https://travis-ci.org/FriendsOfPHP/pickle)

Installation
------------

Clone this repository and install dependencies with
[Composer](http://getcomposer.org/):

```sh
$ composer install
```

Or clone this repository, then run:

```sh
$ cd pickle
$ composer install
```

A phar is also available at http://www.pierrejoye.com/pickle/pickle.phar

Usage
-----

Usage is prety straighforward. For example, to install the memcache extension run the following command:

```sh
$ bin/pickle install memcache
```

You can also use pickle fromyour extension directory, the following command:


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
