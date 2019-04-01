<?php

declare(strict_types = 1);

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

	private function createContainer(): Container
	{
		return new Container([
			'foo' => 'bar',
		]);
	}

	public function testContainer(): void
	{
		$container = $this->createContainer();
		$helper = new ContainerHelper($container);

		Assert::type($container, $helper->getContainer());
		Assert::same('container', $helper->getName());
	}

	public function testParameters(): void
	{
		$container = $this->createContainer();
		$helper = new ContainerHelper($container);

		Assert::contains('bar', $helper->getParameters());
		Assert::same('bar', $helper->getParameter('foo'));
		Assert::false($helper->hasParameter('bar'));
	}

}

(new ContainerHelperTest)->run();
