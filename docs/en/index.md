Quickstart
==========

This extension is here to provide integration of [Symfony Console](https://github.com/symfony/console) into Nette Framework.


Installation
-----------

The best way to install Kdyby/Console is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/console
```

You can enable the extension using your neon config.

```yml
extensions:
	console: Kdyby\Console\DI\ConsoleExtension
```


Minimal configuration
---------------------

This extension creates new configuration section `console`, the absolute minimal configuration might look like this

```yml
console:
	url: http://www.kdyby.org
```

The `url` key specifies reference url that allows you to generate urls using Nette `UI\Presenter` in CLI (which is not possible otherwise). Another useful key is `commands` where you can register new commands. Look at the [Extending](#extending) part.


Writing commands
----------------

Commands are like controllers, but for Symfony Console. Example command might look like this

```php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendNewslettersCommand extends Command
{
	protected function configure()
	{
		$this->setName('app:newsletter')
			->setDescription('Sends the newsletter');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$newsletterSender = $this->getHelper('container')->getByType('Models\NewsletterSender');

		try {
			$newsletterSender->sendNewsletters();
			$output->writeLn('Newsletter sended');
			return 0; // zero return code means everything is ok

		} catch (\Nette\Mail\SmtpException $e) {
			$output->writeLn('<error>' . $e->getMessage() . '</error>');
			return 1; // non-zero return code means error
		}
	}
}
```

The configure method is to name the command and specify arguments and options.
They have a lot of options and you can read about them in Symfony Documentation.

When the command is executed, the execute method is called with two arguments.
First one is command input, which contains all the parsed arguments and options.
The second one is command output which should be used to provide feedback to the developer which ran the command.

Best practise is to return an exit code which specifies if the command ran successfully and can be read by other applications when executed.


Extending
---------

To add a command, simply register it as a service with tag `kdyby.console.command`

```yml
services:
	newsletterCommand:
		class: App\Console\SendNewslettersCommand
		tags: [kdyby.console.command]
```

Alternatively you can use shorter syntax for registering command (without tag). It's useful when you have a lot of commands:

```yml
console:
	commands:
		- App\Console\SendNewslettersCommand
		- App\Console\AnotherCommand
		- App\Console\AnotherCommand2
```

This is called anonymous registration (look at hyphens). You can name your command (`newsletterCommand: App\Console\SendNewslettersCommand`) but mostly it's not necessary.

To add a helper, simply register it as a service with tag `kdyby.console.helper`


```yml
services:
	fooHelper:
		class: App\Console\FooHelper
		tags: [kdyby.console.helper]
```
