<?php

namespace KdybyTests\Console;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
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
	public function getSubscribedEvents()
	{
		return [
			ConsoleEvents::COMMAND,
			ConsoleEvents::EXCEPTION,
			ConsoleEvents::TERMINATE,
		];
	}

	public function command(ConsoleCommandEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand())];
	}

	public function exception(ConsoleExceptionEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand()), $event->getException()];
	}

	public function terminate(ConsoleTerminateEvent $event)
	{
		$this->calls[] = [__FUNCTION__, get_class($event->getCommand()), $event->getExitCode()];
	}

}
