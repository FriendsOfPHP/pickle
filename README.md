pickle - PHP Extension installer
================================

Pickle installs PHP extensions easily on all platforms.

Installation / Usage
--------------------

TODO: how to install & use

Contributing
------------

Fork the project, create a feature branch, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-1](http://www.php-fig.org/psr/psr-1/) and
[PSR-2](http://www.php-fig.org/psr/psr-2/) coding standards.

To avoid CS issues, you should use [php-cs-fixer](http://cs.sensiolabs.org/):


```sh
$ php-cs-fixer fix src/
```

Support
-------

Support is available via the issue tracker in the github project page or via IRC, EFNet, channel #pickle

=======

Running tests
=============

Unit tests are written using [atoum](https://github.com/atoum/atoum).
You will get atoum, among other dependencies, when running `composer install`.
To run tests, you will need to run the following command:

```sh
$ vendor/bin/atoum

# To run tests in a loop, ideal to do TDD
$ vendor/bin/atoum --loop
```
