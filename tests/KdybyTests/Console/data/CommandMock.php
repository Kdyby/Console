<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

class CommandMock extends \Symfony\Component\Console\Command\Command
{

	use \Nette\SmartObject;

	protected function configure()
	{
		$this->setName('test:mock')->setDescription('Just a mock');
	}

}
