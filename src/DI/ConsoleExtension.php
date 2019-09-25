<?php

declare(strict_types = 1);

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
use Nette\Application\Application as NetteApplication;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\DI\Statement;
use Nette\Http\IRequest;
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
	public const HELPER_TAG = self::TAG_HELPER;
	/** @deprecated */
	public const COMMAND_TAG = self::TAG_COMMAND;

	public const TAG_HELPER = 'kdyby.console.helper';
	public const TAG_COMMAND = 'kdyby.console.command';
	public const NETTE_FRAMEWORK = 'Nette Framework';
	public const NETTE_VERSION_30 = '3.0';
	public const NETTE_VERSION_24 = '2.4';

	/**
	 * @var mixed[]
	 */
	public $defaults = [
		'name' => self::NETTE_FRAMEWORK,
		'version' => 'unknown',
		'commands' => [],
		'url' => NULL,
		'urlScriptPath' => NULL,
		'disabled' => TRUE,
		'application' => TRUE,
		'fakeHttp' => TRUE,
	];

	public function __construct()
	{
		$this->defaults['disabled'] = PHP_SAPI !== ConsoleApplication::CLI_SAPI;
		if (\class_exists(\Nette\DI\Definitions\ServiceDefinition::class)) {
			$this->defaults['name'] = self::NETTE_FRAMEWORK;
			$this->defaults['version'] = self::NETTE_VERSION_30;

		} elseif (\class_exists(\Nette\DI\ServiceDefinition::class)) {
			$this->defaults['name'] = self::NETTE_FRAMEWORK;
			$this->defaults['version'] = self::NETTE_VERSION_24;
		}
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$this->loadHelperSet($config);

		$builder->addDefinition($this->prefix('application'))
			->setType(ConsoleApplication::class)
			->setArguments([$config['name'], $config['version']])
			->addSetup('setHelperSet', [$this->prefix('@helperSet')])
			->addSetup('injectServiceLocator');

		if ($config['disabled']) {
			return;
		}

		if ($config['application'] && $this->isNetteApplicationPresent()) {
			$builder->addDefinition($this->prefix('router'))
				->setType(CliRouter::class)
				->setAutowired(FALSE);
		}

		Validators::assert($config, 'array');
		foreach ($config['commands'] as $i => $command) {
			$def = $builder->addDefinition($this->prefix('command.' . $i));
			$def->setFactory(\Nette\DI\Helpers::filterArguments([
				is_string($command) ? new Statement($command) : $command,
			])[0]);

			if (class_exists($def->getEntity())) {
				$def->setType($def->getEntity());
			}

			$def->setAutowired(FALSE);
			$def->addTag(self::TAG_COMMAND);
		}
	}

	/**
	 * @param mixed[] $config
	 */
	protected function loadHelperSet(array $config): void
	{
		$builder = $this->getContainerBuilder();

		$helperSet = $builder->addDefinition($this->prefix('helperSet'))
			->setType(HelperSet::class);

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
		$helpers = array_map(static function ($class) {
			return new Statement($class);
		}, $helperClasses);

		foreach ($helpers as $helper) {
			if (!class_exists($helper->getEntity())) {
				continue;
			}

			if (!self::hasConstructor($helper->getEntity())) {
				$helper->arguments = [];
			}

			$helperSet->addSetup('set', [$helper]);
		}

		$helperSet->addSetup('set', [new Statement(ContainerHelper::class), 'dic']);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = \Nette\DI\Config\Helpers::merge($this->getConfig(), $this->defaults);

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

	/**
	 * @param mixed[] $config
	 */
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

		$routerServiceName = $builder->getByType(IRouter::class) ?: 'router';
		$routerDefinition = $builder->getDefinition($routerServiceName);
		$routerDefinition->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('originalRouter'), clone $routerDefinition);
		$builder->removeDefinition($routerServiceName);
		$builder->addDefinition($routerServiceName)
			->setType(RouteList::class)
			->addSetup('offsetSet', [NULL, $this->prefix('@router')])
			->addSetup('offsetSet', [NULL, $this->prefix('@originalRouter')]);

		/** @var \Nette\DI\Definitions\ServiceDefinition $presenterFactory */
		$presenterFactory = $builder->getDefinition($builder->getByType(IPresenterFactory::class) ?: 'nette.presenterFactory');
		$presenterFactory->addSetup(
			'if (method_exists($service, ?)) { $service->setMapping([? => ?]); }',
			[
				'setMapping',
				'Kdyby',
				'KdybyModule\*\*Presenter',
			]
		);
	}

	/**
	 * @param mixed[] $config
	 * @throws \Nette\Utils\AssertionException
	 */
	protected function beforeCompileFakeHttp(array $config): void
	{
		if (!$config['fakeHttp'] || !$this->isNetteHttpPresent()) {
			return; // ignore
		}

		$builder = $this->getContainerBuilder();

		if (!empty($config['url'])) {
			Validators::assert($config['url'], 'url', 'console.url');
			/** @var \Nette\DI\Definitions\ServiceDefinition $httpRequestFactory */
			$httpRequestFactory = $builder->getDefinition($builder->getByType(\Nette\Http\RequestFactory::class) ?: 'nette.httpRequestFactory');
			$httpRequestFactory
				->setFactory(\Kdyby\Console\HttpRequestFactory::class)
				->addSetup('setFakeRequestUrl', [$config['url'], $config['urlScriptPath']]);
		}
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

}
