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
	public $defaults = array(
		'name' => 'Nette Framework',
		'version' => 'unknown',
		'commands' => array(),
		'url' => NULL,
		'disabled' => TRUE,
	);



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
			->setClass('Kdyby\Console\Application', array($config['name'], $config['version']))
			->addSetup('setHelperSet', array($this->prefix('@helperSet')))
			->addSetup('injectServiceLocator')
			->setInject(FALSE);

		if ($config['disabled']) {
			return;
		}

		$builder->addDefinition($this->prefix('router'))
			->setClass('Kdyby\Console\CliRouter')
			->setAutowired(FALSE)
			->setInject(FALSE);

		Nette\Utils\Validators::assert($config, 'array');
		foreach ($config['commands'] as $command) {
			$def = $builder->addDefinition($this->prefix('command.' . md5(Nette\Utils\Json::encode($command))));
			list($def->factory) = Nette\DI\Compiler::filterArguments(array(
				is_string($command) ? new Statement($command) : $command
			));

			if (class_exists($def->factory->entity)) {
				$def->class = $def->factory->entity;
			}

			$def->setAutowired(FALSE);
			$def->setInject(FALSE);
			$def->addTag(self::TAG_COMMAND);
		}
	}



	protected function loadHelperSet()
	{
		$builder = $this->getContainerBuilder();

		$helperSet = $builder->addDefinition($this->prefix('helperSet'))
			->setClass('Symfony\Component\Console\Helper\HelperSet')
			->setInject(FALSE);

		$helperClasses = array(
			'Symfony\Component\Console\Helper\ProcessHelper',
			'Symfony\Component\Console\Helper\DescriptorHelper',
			'Symfony\Component\Console\Helper\FormatterHelper',
			'Symfony\Component\Console\Helper\QuestionHelper',
			'Symfony\Component\Console\Helper\DebugFormatterHelper',
			'Kdyby\Console\Helpers\PresenterHelper',
		);

		$helpers = array_map(function ($class) {
			return new Statement($class);
		}, $helperClasses);

		// BC
		$helpers[] = new Statement('Symfony\Component\Console\Helper\ProgressHelper', array(FALSE));
		$helpers[] = new Statement('Symfony\Component\Console\Helper\DialogHelper', array(FALSE));

		foreach ($helpers as $helper) {
			if (!class_exists($helper->entity)) {
				continue;
			}

			if (!self::hasConstructor($helper->entity)) {
				$helper->arguments = array();
			}

			$helperSet->addSetup('set', array($helper));
		}

		$helperSet->addSetup('set', array(new Statement('Kdyby\Console\ContainerHelper'), 'dic'));
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if ($config['disabled']) {
			return;
		}

		if (PHP_SAPI === 'cli') {
			$builder->getDefinition($builder->getByType('Nette\Application\Application') ?: 'application')
				->addSetup('$self = $this; $service->onError[] = function ($app, $e) use ($self) {' . "\n" .
					"\t" . '$app->errorPresenter = ?;' . "\n" .
					"\t" . '$app->onShutdown[] = function () { exit(?); };' . "\n" .
					"\t" . '$self->getService(?)->handleException($e); ' . "\n" .
					'}', array(FALSE, 254, $this->prefix('application')));
		}

		$builder->getDefinition($builder->getByType('Nette\Application\IRouter') ?: 'router')
			->addSetup('Kdyby\Console\CliRouter::prependTo($service, ?)', array($this->prefix('@router')));

		$builder->getDefinition($builder->getByType('Nette\Application\IPresenterFactory') ?: 'nette.presenterFactory')
			->addSetup('if (method_exists($service, ?)) { $service->setMapping(array(? => ?)); } ' .
				'elseif (property_exists($service, ?)) { $service->mapping[?] = ?; }', array(
				'setMapping', 'Kdyby', 'KdybyModule\*\*Presenter', 'mapping', 'Kdyby', 'KdybyModule\*\*Presenter'
			));

		if (!empty($config['url'])) {
			if (!preg_match('~^https?://[^/]+(/.*)?$~', $config['url'])) {
				throw new Nette\Utils\AssertionException("The url '{$config['url']}' is not valid, please use this format: 'http://domain.tld/path'.");
			}
			$builder->getDefinition($builder->getByType('Nette\Http\RequestFactory') ?: 'nette.httpRequestFactory')
				->setFactory('Kdyby\Console\HttpRequestFactory')
				->addSetup('setFakeRequestUrl', array($config['url']));
		}

		$helperSet = $builder->getDefinition($this->prefix('helperSet'));
		foreach ($builder->findByTag(self::TAG_HELPER) as $serviceName => $value) {
			$helperSet->addSetup('set', array('@' . $serviceName, $value));
		}

		$app = $builder->getDefinition($this->prefix('application'));
		foreach (array_keys($builder->findByTag(self::TAG_COMMAND)) as $serviceName) {
			$app->addSetup('add', array('@' . $serviceName));
		}

		$sfDispatcher = $builder->getByType('Symfony\Component\EventDispatcher\EventDispatcherInterface') ?: 'events.symfonyProxy';
		if ($builder->hasDefinition($sfDispatcher)
			&& $builder->getDefinition($sfDispatcher)->class === 'Symfony\Component\EventDispatcher\EventDispatcherInterface'
		) {
			$app->addSetup('setDispatcher');
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

}
