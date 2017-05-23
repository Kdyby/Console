# Quickstart

This extension is here to provide integration of [Symfony Console](https://github.com/symfony/console) into Nette Framework.


## Installation

The best way to install Kdyby/Console is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/console
```

You can enable the extension using your neon config.

```yml
extensions:
    console: Kdyby\Console\DI\ConsoleExtension
```

## Minimal configuration

This extension creates new configuration section `console`, the absolute minimal configuration might look like this

```yml
console:
    url: http://www.kdyby.org
```

The `url` key specifies reference url that allows you to generate urls using `LinkGenerator` in CLI (which is not possible otherwise).


## Running the console

It is suggested, that you create a `bin/console` file, with the following contents

```php
#!/usr/bin/env php
<?php
/** @var \Nette\DI\Container $container */
$container = require __DIR__ . '/../app/bootstrap.php';
$container->getByType(\Symfony\Component\Console\Application::class)->run();
```

Make sure the console script is executable by running `chmod +x bin/console`.

And test it by running `php bin/console` (but just `bin/console` should work too), it should list all available commands.

## Writing commands

Commands are like controllers, but for Symfony Console. Example command might look like this

```php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendNewslettersCommand extends Command
{

    /** @var \Models\NewsletterSender */
    private $newsletterSender;

    public function __construct(NewsletterSender $newsletterSender)
    {
        $this->newsletterSender = $newsletterSender;
    }

    protected function configure(): void
    {
        $this->setName('app:newsletter')
            ->setDescription('Sends the newsletter');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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

The configure method is to name the command and specify arguments and options.
They have a lot of options and you can read about them in Symfony Documentation.

When the command is executed, the execute method is called with two arguments.
First one is command input, which contains all the parsed arguments and options.
The second one is command output which should be used to provide feedback to the developer which ran the command.

Best practise is to return an exit code which specifies if the command ran successfully and can be read by other applications when executed.


## Registering commands

To add a command, register it as a service with tag `kdyby.console.command`

```yml
services:
    newsletterCommand:
        class: App\Console\SendNewslettersCommand
        tags: [kdyby.console.command]
```

To add a helper, register it as a service with tag `kdyby.console.helper`


```yml
services:
    fooHelper:
        class: App\Console\FooHelper
        tags: [kdyby.console.helper]
```

### Shorter configuration

If you want to register all your commands and don't want to write tags to all of them, you can use the Nette Decorator extension

```yml
decorator:
    Symfony\Component\Console\Command\Command:
        tags: [kdyby.console.command]

services:
    - App\Console\SendNewslettersCommand
```

Nette will add the tag to all the command services automatically and they will get picked by Kdyby/Console and registered as commands.
