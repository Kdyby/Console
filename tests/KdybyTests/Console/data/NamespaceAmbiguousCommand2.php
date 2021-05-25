<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tester\Assert;

class NamespaceAmbiguousCommand2 extends \Symfony\Component\Console\Command\Command
{

	use \Nette\SmartObject;

	protected function configure()
	{
		$this->setName('namespace2:ambiguous');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}
