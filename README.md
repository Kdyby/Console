Kdyby/Console
======

[![Build Status](https://travis-ci.org/Kdyby/Console.svg?branch=master)](https://travis-ci.org/Kdyby/Console)
[![Downloads this Month](https://img.shields.io/packagist/dm/kdyby/console.svg)](https://packagist.org/packages/kdyby/console)
[![Latest stable](https://img.shields.io/packagist/v/kdyby/console.svg)](https://packagist.org/packages/kdyby/console)


Requirements
------------

Kdyby/Console requires PHP 5.4 or higher.

- [Nette Framework](https://github.com/nette/nette)
- [Symfony Console](https://github.com/symfony/Console)


Installation
------------

The best way to install Kdyby/Console is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/console
```

Then enable the extension in your `config.neon`:

```yml
extensions:
	console: Kdyby\Console\DI\ConsoleExtension
```

And append your commands into `console` section:
```yml
console:
	disable: false      # optional, can be used to disable console extension entirely
	helpers:            # optional, helpers go here
		- App\Console\FooHelper 
	commands:           # define your commands in this section. Full Nette DI is supported.
		- App\Console\SendNewslettersCommand 
		- App\Console\AnotherCommand 
		- App\Console\AnotherCommand2
```


Documentation
------------

Learn more in the [documentation](https://github.com/Kdyby/Console/blob/master/docs/en/index.md).


-----

Homepage [http://www.kdyby.org](http://www.kdyby.org) and repository [http://github.com/kdyby/Console](http://github.com/kdyby/Console).
