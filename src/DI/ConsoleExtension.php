<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console\DI;

use Kdyby;
use Nette;
use Nette\DI\Statement;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ConsoleExtension extends Nette\DI\CompilerExtension
{
	/** @deprecated */
	const HELPER_TAG = self::TAG_HELPER;
	/** @deprecated */
	const COMMAND_TAG = self::TAG_COMMAND;

	const TAG_HELPER = 'kdyby.console.helper';
	const TAG_COMMAND = 'kdyby.console.command';

	/**
	 * @var array
	 */
	public $defaults = [
		'name' => 'Nette Framework',
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
		$this->defaults['disabled'] = PHP_SAPI !== 'cli';
		if (class_exists('Nette\Framework')) {
			$this->defaults['name'] = Nette\Framework::NAME;
			$this->defaults['version'] = Nette\Framework::VERSION;
		}
	}



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$this->loadHelperSet($config);

		$builder->addDefinition($this->prefix('application'))
			->setClass('Kdyby\Console\Application', [$config['name'], $config['version']])
			->addSetup('setHelperSet', [$this->prefix('@helperSet')])
			->addSetup('injectServiceLocator');

		if ($config['disabled']) {
			return;
		}

		if ($config['application'] && $this->isNetteApplicationPresent()) {
			$builder->addDefinition($this->prefix('router'))
				->setClass('Kdyby\Console\CliRouter')
				->setAutowired(FALSE);
		}

		Nette\Utils\Validators::assert($config, 'array');
		foreach ($config['commands'] as $i => $command) {
			$def = $builder->addDefinition($this->prefix('command.' . $i));
			$def->setFactory(Nette\DI\Compiler::filterArguments([
				is_string($command) ? new Statement($command) : $command
			])[0]);

			if (class_exists($def->getEntity())) {
				$def->setClass($def->getEntity());
			}

			$def->setAutowired(FALSE);
			$def->addTag(self::TAG_COMMAND);
		}
	}



	protected function loadHelperSet(array $config)
	{
		$builder = $this->getContainerBuilder();

		$helperSet = $builder->addDefinition($this->prefix('helperSet'))
			->setClass('Symfony\Component\Console\Helper\HelperSet');

		$helperClasses = [
			'Symfony\Component\Console\Helper\ProcessHelper',
			'Symfony\Component\Console\Helper\DescriptorHelper',
			'Symfony\Component\Console\Helper\FormatterHelper',
			'Symfony\Component\Console\Helper\QuestionHelper',
			'Symfony\Component\Console\Helper\DebugFormatterHelper',
		];

		if ($config['application'] && $this->isNetteApplicationPresent()) {
			$helperClasses[] = 'Kdyby\Console\Helpers\PresenterHelper';
		}

		$helpers = array_map(function ($class) {
			return new Statement($class);
		}, $helperClasses);

		// BC
		$helpers[] = new Statement('Symfony\Component\Console\Helper\ProgressHelper', [FALSE]);
		$helpers[] = new Statement('Symfony\Component\Console\Helper\DialogHelper', [FALSE]);

		foreach ($helpers as $helper) {
			if (!class_exists($helper->getEntity())) {
				continue;
			}

			if (!self::hasConstructor($helper->getEntity())) {
				$helper->arguments = [];
			}

			$helperSet->addSetup('set', [$helper]);
		}

		$helperSet->addSetup('set', [new Statement('Kdyby\Console\ContainerHelper'), 'dic']);
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if ($config['disabled']) {
			return;
		}

		$this->beforeCompileHookApplication($config);
		$this->beforeCompileFakeHttp($config);

		$helperSet = $builder->getDefinition($this->prefix('helperSet'));
		foreach ($builder->findByTag(self::TAG_HELPER) as $serviceName => $value) {
			$helperSet->addSetup('set', ['@' . $serviceName, $value]);
		}

		$app = $builder->getDefinition($this->prefix('application'));
		foreach ($builder->findByTag(self::TAG_COMMAND) as $serviceName => $_) {
			$app->addSetup('add', ['@' . $serviceName]);
		}

		$sfDispatcher = $builder->getByType('Symfony\Component\EventDispatcher\EventDispatcherInterface') ?: 'events.symfonyProxy';
		if ($builder->hasDefinition($sfDispatcher)
			&& $builder->getDefinition($sfDispatcher)->getClass() === 'Symfony\Component\EventDispatcher\EventDispatcherInterface'
		) {
			$app->addSetup('setDispatcher');
		}
	}



	protected function beforeCompileHookApplication(array $config)
	{
		if (!$config['application'] || !$this->isNetteApplicationPresent()) {
			return; // ignore
		}

		$builder = $this->getContainerBuilder();

		if (PHP_SAPI === 'cli') {
			$builder->getDefinition($builder->getByType('Nette\Application\Application') ?: 'application')
				->addSetup('$self = $this; $service->onError[] = function ($app, $e) use ($self) {' . "\n" .
					"\t" . '$app->errorPresenter = ?;' . "\n" .
					"\t" . '$app->onShutdown[] = function () { exit(?); };' . "\n" .
					'}', [FALSE, 254]);
		}

		$routerServiceName = $builder->getByType('Nette\Application\IRouter') ?: 'router';
		$builder->addDefinition($this->prefix('originalRouter'), $builder->getDefinition($routerServiceName))
			->setAutowired(FALSE);

		$builder->removeDefinition($routerServiceName);

		$builder->addDefinition($routerServiceName)
			->setClass('Nette\Application\Routers\RouteList')
			->addSetup('offsetSet', [NULL, $this->prefix('@router')])
			->addSetup('offsetSet', [NULL, $this->prefix('@originalRouter')]);

		$builder->getDefinition($builder->getByType('Nette\Application\IPresenterFactory') ?: 'nette.presenterFactory')
			->addSetup('if (method_exists($service, ?)) { $service->setMapping([? => ?]); } ' .
				'elseif (property_exists($service, ?)) { $service->mapping[?] = ?; }', [
				'setMapping', 'Kdyby', 'KdybyModule\*\*Presenter', 'mapping', 'Kdyby', 'KdybyModule\*\*Presenter'
			]);
	}



	protected function beforeCompileFakeHttp(array $config)
	{
		if (!$config['fakeHttp'] || !$this->isNetteHttpPresent()) {
			return; // ignore
		}

		$builder = $this->getContainerBuilder();

		if (!empty($config['url'])) {
			Nette\Utils\Validators::assert($config['url'], 'url', 'console.url');
			$builder->getDefinition($builder->getByType('Nette\Http\RequestFactory') ?: 'nette.httpRequestFactory')
				->setFactory('Kdyby\Console\HttpRequestFactory')
				->addSetup('setFakeRequestUrl', [$config['url'], $config['urlScriptPath']]);
		}
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('console', new ConsoleExtension());
		};
	}



	/**
	 * @param string $class
	 * @return bool
	 */
	private static function hasConstructor($class)
	{
		return class_exists($class) && method_exists($class, '__construct');
	}



	/**
	 * @return bool
	 */
	private function isNetteApplicationPresent()
	{
		return (bool) $this->compiler->getExtensions('Nette\Bridges\ApplicationDI\ApplicationExtension');
	}



	/**
	 * @return bool
	 */
	private function isNetteHttpPresent()
	{
		return interface_exists('Nette\Http\IRequest');
	}

}
