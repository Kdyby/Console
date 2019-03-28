<?php

namespace KdybyTests\Console;

class CommandMock extends \Symfony\Component\Console\Command\Command
{

	protected function configure()
	{
		$this->setName('test:mock')->setDescription('Just a mock');
	}

}
