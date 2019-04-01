<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class ConsoleListener implements \Kdyby\Events\Subscriber
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var string[][]
	 */
	public $calls = [];

	/**
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [
			ConsoleEvents::COMMAND,
			ConsoleEvents::TERMINATE,
		];
	}

	public function command(ConsoleCommandEvent $event): void
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand())];
	}

	public function terminate(ConsoleTerminateEvent $event): void
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand()), $event->getExitCode()];
	}

}
