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

	use \Nette\SmartObject;

	protected function configure()
	{
		$this->setName('arg')
			->addArgument('first', InputArgument::REQUIRED)
			->addArgument('second')
			->addOption('existent', 'e', InputOption::VALUE_REQUIRED)
			->addOption('no-value', 'x', InputOption::VALUE_NONE);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 * @throws \Tester\AssertException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}
