<?php declare(strict_types=1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console\DI;

use Kdyby\Console\Application as ConsoleApplication;
use Kdyby\Console\CliRouter;
use Kdyby\Console\ContainerHelper;
use Kdyby\Console\Helpers\PresenterHelper;
use Kdyby\Console\HttpRequestFactory as FakeHttpRequestFactory;
use Nette\Application\Application as NetteApplication;
use Nette\Application\IPresenterFactory;
use Nette\Application\Routers\RouteList;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\Framework as NetteFramework;
use Nette\Http\IRequest;
use Nette\Http\RequestFactory as NetteRequestFactory;
use Nette\Routing\Router;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Validators;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConsoleExtension extends \Nette\DI\CompilerExtension
{

	/** @deprecated */
	const HELPER_TAG = self::TAG_HELPER;
	/** @deprecated */
	const COMMAND_TAG = self::TAG_COMMAND;

	const TAG_HELPER = 'kdyby.console.helper';
	const TAG_COMMAND = 'kdyby.console.command';

	/**
	 * @var mixed[]
	 */
	public $defaults = [
		'name' => 'Nette Framework',
		'version' => 'unknown',
		'disabled' => TRUE,
	];

	public function __construct()
	{
		$this->defaults['disabled'] = PHP_SAPI !== ConsoleApplication::CLI_SAPI;
		if (class_exists(NetteFramework::class)) {
			$this->defaults['name'] = NetteFramework::NAME;
			$this->defaults['version'] = NetteFramework::VERSION;
		}
	}

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->getConfig();

		$this->loadHelperSet($config);

		$builder->addDefinition($this->prefix('application'))
			->setFactory(ConsoleApplication::class, [$config['name'], $config['version']])
			->addSetup('setHelperSet', [$this->prefix('@helperSet')])
			->addSetup('injectServiceLocator');

		if ($config['disabled']) {
			return;
		}

		if ($config['application'] && $this->isNetteApplicationPresent()) {
			$builder->addDefinition($this->prefix('router'))
				->setClass(CliRouter::class)
				->setAutowired(FALSE);
		}

		foreach ($config['commands'] as $i => $command) {
			$def = $builder->addDefinition($this->prefix('command.' . $i));
			$def->setFactory(Helpers::filterArguments([
				is_string($command) ? new Statement($command) : $command,
			])[0]);

			/** @var string $entity */
			$entity = $def->getEntity();

			if (class_exists($entity)) {
				$def->setType($entity);
			}

			$def->setAutowired(FALSE);
			$def->addTag(self::TAG_COMMAND);
		}
	}

	protected function loadHelperSet(array $config)
	{
		$builder = $this->getContainerBuilder();

		$helperSet = $builder->addDefinition($this->prefix('helperSet'))
			->setClass(HelperSet::class);

		$helperClasses = [
			ProcessHelper::class,
			DescriptorHelper::class,
			FormatterHelper::class,
			QuestionHelper::class,
			DebugFormatterHelper::class,
		];

		if ($config['application'] && $this->isNetteApplicationPresent()) {
			$helperClasses[] = PresenterHelper::class;
		}

		/** @var \Nette\DI\Statement[] $helpers */
		$helpers = array_map(function ($class) {
			return new Statement($class);
		}, $helperClasses);

		foreach ($helpers as $helper) {
			/** @var string $entity */
			$entity = $helper->getEntity();
			if (!class_exists($entity)) {
				continue;
			}

			if (!self::hasConstructor($entity)) {
				$helper->arguments = [];
			}

			$helperSet->addSetup('set', [$helper]);
		}

		$helperSet->addSetup('set', [new Statement(ContainerHelper::class), 'dic']);
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->getConfig();

		if ($config['disabled']) {
			return;
		}

		$this->beforeCompileHookApplication($config);
		$this->beforeCompileFakeHttp($config);

		/** @var \Nette\DI\Definitions\ServiceDefinition $helperSet */
		$helperSet = $builder->getDefinition($this->prefix('helperSet'));
		foreach ($builder->findByTag(self::TAG_HELPER) as $serviceName => $value) {
			$helperSet->addSetup('set', ['@' . $serviceName, $value]);
		}

		/** @var \Nette\DI\Definitions\ServiceDefinition $app */
		$app = $builder->getDefinition($this->prefix('application'));
		foreach ($builder->findByTag(self::TAG_COMMAND) as $serviceName => $ignore) {
			$app->addSetup('add', ['@' . $serviceName]);
		}

		$sfDispatcher = $builder->getByType(EventDispatcherInterface::class) ?: 'events.symfonyProxy';
		if ($builder->hasDefinition($sfDispatcher)
			&& $builder->getDefinition($sfDispatcher)->getClass() === EventDispatcherInterface::class
		) {
			$app->addSetup('setDispatcher');
		}
	}

	protected function beforeCompileHookApplication(array $config): void
	{
		if (!$config['application'] || !$this->isNetteApplicationPresent()) {
			return; // ignore
		}

		$builder = $this->getContainerBuilder();

		if (PHP_SAPI === ConsoleApplication::CLI_SAPI) {
			/** @var \Nette\DI\Definitions\ServiceDefinition $app */
			$app = $builder->getDefinition($builder->getByType(NetteApplication::class) ?: 'application');
			$app->addSetup('$self = $this; $service->onError[] = function ($app, $e) use ($self) {' . "\n" .
					"\t" . '$app->errorPresenter = ?;' . "\n" .
					"\t" . '$app->onShutdown[] = function () { exit(?); };' . "\n" .
					'}', [FALSE, 254]);
		}

		$routerServiceName = $builder->getByType(Router::class) ?: 'router';
		$builder->addDefinition($this->prefix('originalRouter'), clone $builder->getDefinition($routerServiceName))
			->setAutowired(FALSE);

		$builder->removeDefinition($routerServiceName);

		$builder->addDefinition($routerServiceName)
			->setClass(RouteList::class)
			->addSetup('offsetSet', [NULL, $this->prefix('@router')])
			->addSetup('offsetSet', [NULL, $this->prefix('@originalRouter')]);

		/** @var \Nette\DI\Definitions\ServiceDefinition $definition */
		$definition = $builder->getDefinition($builder->getByType(IPresenterFactory::class) ?: 'nette.presenterFactory');
		$definition->addSetup(
			'if (method_exists($service, ?)) { $service->setMapping([? => ?]); }',
			[
				'setMapping',
				'Kdyby',
				'KdybyModule\*\*Presenter',
			]
		);
	}

	protected function beforeCompileFakeHttp(array $config): void
	{
		if (!$config['fakeHttp'] || !$this->isNetteHttpPresent()) {
			return; // ignore
		}

		$builder = $this->getContainerBuilder();

		if (!empty($config['url'])) {
			Validators::assert($config['url'], 'url', 'console.url');
			/** @var \Nette\DI\Definitions\ServiceDefinition $definition */
			$definition = $builder->getDefinition($builder->getByType(NetteRequestFactory::class) ?: 'nette.httpRequestFactory');
			$definition->setFactory(FakeHttpRequestFactory::class)
				->addSetup('setFakeRequestUrl', [$config['url'], $config['urlScriptPath']]);
		}
	}

	public static function register(Configurator $configurator): void
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('console', new ConsoleExtension());
		};
	}

	private static function hasConstructor(string $class): bool
	{
		return class_exists($class) && method_exists($class, '__construct');
	}

	private function isNetteApplicationPresent(): bool
	{
		return (bool) $this->compiler->getExtensions(ApplicationExtension::class);
	}

	private function isNetteHttpPresent(): bool
	{
		return interface_exists(IRequest::class);
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'name' => Expect::string($this->defaults['name']),
			'version' => Expect::string($this->defaults['version']),
			'commands' => Expect::array(),
			'url' => Expect::string(),
			'urlScriptPath' => Expect::string(),
			'disabled' => Expect::bool($this->defaults['disabled']),
			'application' => Expect::bool(TRUE),
			'fakeHttp' => Expect::bool(TRUE),
		]);
	}

}
