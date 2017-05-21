<?php

namespace KdybyTests\Console;

class CommandMock extends \Symfony\Component\Console\Command\Command
{

	use \Kdyby\StrictObjects\Scream;

	protected function configure()
	{
		$this->setName('test:mock')->setDescription('Just a mock');
	}

}
