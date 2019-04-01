<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tester\Assert;

class AmbiguousCommand1 extends \Symfony\Component\Console\Command\Command
{

	use \Kdyby\StrictObjects\Scream;

	protected function configure(): void
	{
		$this->setName('ambiguous1');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}
