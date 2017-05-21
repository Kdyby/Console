<?php

/**
 * Test: Kdyby\Console\ContainerHelper.
 *
 * @testCase
 */

namespace KdybyTests\Console;

use Kdyby\Console\ContainerHelper;
use Nette\DI\Container;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class ContainerHelperTest extends \Tester\TestCase
{

	/**
	 * @return \Nette\DI\Container
	 */
	private function createContainer()
	{
		return new Container([
			'foo' => 'bar',
		]);
	}

	public function testContainer()
	{
		$container = $this->createContainer();
		$helper = new ContainerHelper($container);

		Assert::type($container, $helper->getContainer());
		Assert::same('container', $helper->getName());
	}

	public function testParameters()
	{
		$container = $this->createContainer();
		$helper = new ContainerHelper($container);

		Assert::contains('bar', $helper->getParameters());
		Assert::same('bar', $helper->getParameter('foo'));
		Assert::false($helper->hasParameter('bar'));
	}

}

(new ContainerHelperTest)->run();
