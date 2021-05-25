<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tester\Assert;

class SameArgsCommandTwo extends \Symfony\Component\Console\Command\Command
{

	use \Nette\SmartObject;

	/**
	 * @var \KdybyTests\Console\ArgCommand
	 */
	private $argCommand;
	/**
	 * @var \KdybyTests\Console\TypoCommand
	 */
	private $typoCommand;


	public function __construct(ArgCommand $argCommand, TypoCommand $typoCommand)
	{
		parent::__construct();
		$this->argCommand = $argCommand;
		$this->typoCommand = $typoCommand;
	}

	protected function configure()
	{
		$this->setName('sameArgsCommand:two');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		Assert::fail("This command shouldn't have been executed.");
	}


	public function argCommand() : \KdybyTests\Console\ArgCommand
	{
		return $this->argCommand;
	}


	public function typoCommand() : \KdybyTests\Console\TypoCommand
	{
		return $this->typoCommand;
	}

}
