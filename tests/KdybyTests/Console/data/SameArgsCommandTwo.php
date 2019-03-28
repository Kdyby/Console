<?php

namespace KdybyTests\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tester\Assert;

class SameArgsCommandTwo extends \Symfony\Component\Console\Command\Command
{

	public function __construct(ArgCommand $argCommand, TypoCommand $typoCommand)
	{
		parent::__construct();
	}

	protected function configure()
	{
		$this->setName('sameArgsCommand:two');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}

}
