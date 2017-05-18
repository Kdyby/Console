<?php

/**
 * Test: Kdyby\Console\HttpRequestFactory.
 *
 * @testCase Kdyby\Console\HttpRequestFactoryTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Console
 */

namespace KdybyTests\Console;

use Kdyby;
use KdybyModule\CliPresenter;
use Nette\Application\LinkGenerator;
use Nette\Application\PresenterFactory;
use Nette\Application\Routers\Route;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HttpRequestFactoryTest extends Tester\TestCase
{

	public function testScriptPath()
	{
		$requestFactory = new Kdyby\Console\HttpRequestFactory();
		$requestFactory->setFakeRequestUrl(
			'http://domain.tld/path/',
			'/path/'
		);

		$httpRequest = $requestFactory->createHttpRequest();
		Assert::same('cli', $httpRequest->getMethod());

		$presenterFactory = new PresenterFactory();
		$presenterFactory->setMapping(['Kdyby' => 'KdybyModule\*\*Presenter']);
		$linkGenerator = new LinkGenerator(
			new Route('ABCDEF', ['presenter' => CliPresenter::NAME, 'action' => 'default']),
			$httpRequest->getUrl(),
			$presenterFactory
		);

		$url = $linkGenerator->link('Kdyby:Cli:default', ['code' => 'brown-alert']);
		Assert::same('http://domain.tld/path/ABCDEF?code=brown-alert', $url);
	}

}

(new HttpRequestFactoryTest())->run();
