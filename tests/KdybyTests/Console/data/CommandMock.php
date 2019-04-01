<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

class CommandMock extends \Symfony\Component\Console\Command\Command
{

	use \Kdyby\StrictObjects\Scream;

	protected function configure(): void
	{
		$this->setName('test:mock')->setDescription('Just a mock');
	}

}
