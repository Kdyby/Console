<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tester\Assert;

class ArgCommand extends \Symfony\Component\Console\Command\Command
{

	use \Kdyby\StrictObjects\Scream;

	protected function configure(): void
	{
		$this->setName('arg')
			->addArgument('first', InputArgument::REQUIRED)
			->addArgument('second')
			->addOption('existent', 'e', InputOption::VALUE_REQUIRED)
			->addOption('no-value', 'x', InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}
