kdyby/console
=============

This extension provides integration of [Symfony Console](https://github.com/symfony/console) into [Nette Framework](https://www.nette.org).

It allows you to create command-line commands directly within your application. These commands can be used for recurring tasks, as cronjobs, maintenances, imports and/or big things like sending newsletters.


Installation
-----------

Fastest way is to use [Composer](http://getcomposer.org/) - run following command in your project root:

```sh
$ composer require kdyby/console
```

Minimal configuration
---------------------

First register new extension in your `config.neon`

```
extensions:
	console: Kdyby\Console\DI\ConsoleExtension
```

This creates new configuration section `console`, the absolute minimal configuration might look like this:

```yml
console:
	url: http://www.kdyby.org
```

The `url` key specifies reference url, that allows you to generate urls using Nette `UI\Presenter` in CLI (which is not possible otherwise).

Now, your nette installation is ready to run commands! Try it:

```sh
$ php www/index.php
```

Writing commands
----------------

Example command might look like this:

```php
namespace App\Console;

use App\Models;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendNewslettersCommand extends Command
{
	/** @var Models\NewsletterSender */
	private $newsletterSender;

	/**
	 * @param Models\NewsletterSender $sender
	 */
	protected function __construct(Models\NewsletterSender $sender)
	{
		parent::__construct('app:newsletter'); // <-- run with `php www/index.php app:newsletter`
		$this->setDescription('Sends the newsletter');
		$this->newsletterSender = $sender;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$this->newsletterSender->sendNewsletters();
			$output->writeLn('Newsletter sent');
			return 0; // zero return code means everything is ok

		} catch (\Nette\Mail\SmtpException $e) {
			$output->writeLn('<error>' . $e->getMessage() . '</error>');
			return 1; // non-zero return code means error
		}
	}
}
```


In `__construct`, we setup the name of the command, then set some description and set all dependencies.
  
Every command contains an `execute` function, which is called by Symfony console, whenever the command should be executed. The arguments handle either input parameters, and/or allow you to write to output stream.
 
Best practice is to return an exit code, which specifies if the command ran successfully or not. This code can be read by other applications, when they execute your app. This is useful for cronjobs. 

Every command needs to be registered in commands section of `config.neon`:

```yml
console:
	commands:
		- App\Console\SendNewslettersCommand
```

Extending
---------

To add a command, simply register it inside of the `console.commands` section:

```yml
console:
	commands:
		- App\Console\SendNewslettersCommand
		- App\Console\AnotherCommand
		- App\Console\AnotherCommand2
```

If you want to add a new [console helper](http://symfony.com/doc/current/components/console/helpers/index.html), use following syntax:

```yml
console:
	helpers:
		- App\Console\FooHelper
```
