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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ConsoleExtension extends Nette\DI\CompilerExtension
{

	const HELPER_TAG = 'kdyby.console.helper';
	const COMMAND_TAG = 'kdyby.console.command';

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

		$helperClasses = array(
			'Symfony\Component\Console\Helper\DialogHelper',
			'Symfony\Component\Console\Helper\FormatterHelper',
			'Symfony\Component\Console\Helper\QuestionHelper',
			'Kdyby\Console\Helpers\PresenterHelper',
		);

		if (class_exists('Symfony\Component\Console\Helper\ProgressHelper')) {
			$helperClasses[] = 'Symfony\Component\Console\Helper\ProgressHelper';
		}

		$helperClasses = array_map(function ($class) { return new Nette\DI\Statement($class); }, $helperClasses);

		$builder->addDefinition($this->prefix('helperSet'))
			->setClass('Symfony\Component\Console\Helper\HelperSet', array($helperClasses))
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('application'))
			->setClass('Kdyby\Console\Application', array($config['name'], $config['version']))
			->addSetup('setHelperSet', array($this->prefix('@helperSet')))
			->addSetup('injectServiceLocator')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('dicHelper'))
			->setClass('Kdyby\Console\ContainerHelper')
			->addTag(self::HELPER_TAG, 'dic');

		if ($config['disabled']) {
			return;
		}

		if (PHP_SAPI === 'cli') {
			$builder->getDefinition('application')
				->addSetup('$self = $this; $service->onError[] = function ($app, $e) use ($self) {' . "\n" .
					"\t" . '$app->errorPresenter = ?;' . "\n" .
					"\t" . '$app->onShutdown[] = function () { exit(?); };' . "\n" .
					"\t" . '$self->getService(?)->handleException($e); ' . "\n" .
					'}', array(FALSE, 254, $this->prefix('application')));
		}

		$builder->addDefinition($this->prefix('router'))
			->setClass('Kdyby\Console\CliRouter')
			->setAutowired(FALSE)
			->setInject(FALSE);

		$builder->getDefinition('router')
			->addSetup('Kdyby\Console\CliRouter::prependTo($service, ?)', array($this->prefix('@router')));

		$builder->getDefinition('nette.presenterFactory')
			->addSetup('if (method_exists($service, ?)) { $service->setMapping(array(? => ?)); } ' .
				'elseif (property_exists($service, ?)) { $service->mapping[?] = ?; }', array(
				'setMapping', 'Kdyby', 'KdybyModule\*\*Presenter', 'mapping', 'Kdyby', 'KdybyModule\*\*Presenter'
			));

		if (!empty($config['url'])) {
			if (!preg_match('~^https?://[^/]+(/.*)?$~', $config['url'])) {
				throw new Nette\Utils\AssertionException("The url '{$config['url']}' is not valid, please use this format: 'http://domain.tld/path'.");
			}
			$builder->getDefinition('nette.httpRequestFactory')
				->setClass('Kdyby\Console\HttpRequestFactory')
				->addSetup('setFakeRequestUrl', array($config['url']));
		}

		Nette\Utils\Validators::assert($config, 'array');
		foreach ($config['commands'] as $command) {
			$def = $builder->addDefinition($this->prefix('command.' . md5(Nette\Utils\Json::encode($command))));
			list($def->factory) = Nette\DI\Compiler::filterArguments(array(
				is_string($command) ? new Nette\DI\Statement($command) : $command
			));

			if (class_exists($def->factory->entity)) {
				$def->class = $def->factory->entity;
			}

			$def->setAutowired(FALSE);
			$def->setInject(FALSE);
			$def->addTag(self::COMMAND_TAG);
		}
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if ($config['disabled']) {
			return;
		}

		$helperSet = $builder->getDefinition($this->prefix('helperSet'));
		foreach ($builder->findByTag(self::HELPER_TAG) as $serviceName => $value) {
			$helperSet->addSetup('set', array('@' . $serviceName, $value));
		}

		$app = $builder->getDefinition($this->prefix('application'));
		foreach (array_keys($builder->findByTag(self::COMMAND_TAG)) as $serviceName) {
			$app->addSetup('add', array('@' . $serviceName));
		}

		if ($builder->hasDefinition('events.symfonyProxy')
			&& $builder->getDefinition('events.symfonyProxy')->class === 'Symfony\Component\EventDispatcher\EventDispatcherInterface'
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

}
