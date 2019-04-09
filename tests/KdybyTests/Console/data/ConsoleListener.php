<?php

namespace KdybyTests\Console;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class ConsoleListener implements \Kdyby\Events\Subscriber
{

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
			ConsoleEvents::COMMAND,
			ConsoleEvents::TERMINATE,
		];
	}

	public function command(ConsoleCommandEvent $event)
	{
		/** @var \Symfony\Component\Console\Command\Command $command */
		$command = $event->getCommand();

		$this->calls[] = [__FUNCTION__, get_class($command)];
	}

	public function terminate(ConsoleTerminateEvent $event)
	{
		/** @var \Symfony\Component\Console\Command\Command $command */
		$command = $event->getCommand();

		$this->calls[] = [__FUNCTION__, get_class($command), $event->getExitCode()];
	}

}
