<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class ConsoleListener implements \Kdyby\Events\Subscriber
{

	use \Nette\SmartObject;

	/**
	 * @var string[][]
	 */
	public $calls = [];

	/**
	 * @return string[]
	 */
	public function getSubscribedEvents()
	{
		return [
			ConsoleEvents::COMMAND => 'command',
			ConsoleEvents::TERMINATE => 'terminate',
		];
	}

	public function command(ConsoleCommandEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand())];
	}

	public function terminate(ConsoleTerminateEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand()), $event->getExitCode()];
	}

}
