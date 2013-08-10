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
class ConsoleExtension extends Nette\Config\CompilerExtension
{

	const HELPER_TAG = 'kdyby.console.helper';
	const COMMAND_TAG = 'kdyby.console.command';

	/**
	 * @var array
	 */
	public $defaults = array(
		'name' => Nette\Framework::NAME,
		'version' => Nette\Framework::VERSION,
		'commands' => array(),
		'url' => NULL,
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('helperSet'))
			->setClass('Symfony\Component\Console\Helper\HelperSet', array(array(
				new Nette\DI\Statement('Symfony\Component\Console\Helper\DialogHelper'),
				new Nette\DI\Statement('Symfony\Component\Console\Helper\FormatterHelper'),
				new Nette\DI\Statement('Symfony\Component\Console\Helper\ProgressHelper'),
				new Nette\DI\Statement('Kdyby\Console\Helpers\PresenterHelper'),
			)));

		$builder->addDefinition($this->prefix('application'))
			->setClass('Kdyby\Console\Application', array($config['name'], $config['version']))
			->addSetup('setHelperSet', array($this->prefix('@helperSet')));

		$builder->addDefinition($this->prefix('router'))
			->setClass('Kdyby\Console\CliRouter')
			->setAutowired(FALSE);

		$builder->getDefinition('router')
			->addSetup('Kdyby\Console\CliRouter::prependTo($service, ?)', array($this->prefix('@router')));

		$builder->getDefinition('nette.presenterFactory')
			->addSetup('if (method_exists($service, ?)) { $service->setMapping(array(? => ?)); } ' .
				'elseif (property_exists($service, ?)) { $service->mapping[?] = ?; }', array(
				'setMapping', 'Kdyby', 'KdybyModule\*\*Presenter', 'mapping', 'Kdyby', 'KdybyModule\*\*Presenter'
			));

		if (!empty($config['url'])) {
			if (!preg_match('~^https?://[^/]+\\.[a-z]+(/.*)?$~', $config['url'])) {
				throw new Nette\Utils\AssertionException("The url '{$config['url']}' is not valid, please use this format: 'http://domain.tld/path'.");
			}
			$builder->getDefinition('nette.httpRequestFactory')
				->setClass('Kdyby\Console\HttpRequestFactory')
				->addSetup('setFakeRequestUrl', $config['url']);

		} elseif (PHP_SAPI === 'cli' && empty($config['url'])) {
			trigger_error("You should probably specify an url key in {$this->name} extension, otherwise you will be unable to generate urls.", E_USER_NOTICE);
		}

		$builder->addDefinition($this->prefix('dicHelper'))
			->setClass('Kdyby\Console\ContainerHelper')
			->addTag(self::HELPER_TAG, 'dic');

		Nette\Utils\Validators::assert($config, 'array');
		foreach ($config['commands'] as $command) {
			$def = $builder->addDefinition($this->prefix('command.' . md5(Nette\Utils\Json::encode($command))));
			list($def->factory) = Nette\Config\Compiler::filterArguments(array(
				is_string($command) ? new Nette\DI\Statement($command) : $command
			));

			if (class_exists($def->factory->entity)) {
				$def->class = $def->factory->entity;
			}

			$def->setAutowired(FALSE);
			$def->addTag(self::COMMAND_TAG);
		}
	}



	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$helperSet = $builder->getDefinition($this->prefix('helperSet'));
		foreach ($builder->findByTag(self::HELPER_TAG) as $serviceName => $value) {
			$helperSet->addSetup('set', array('@' . $serviceName, $value));
		}

		$app = $builder->getDefinition($this->prefix('application'));
		foreach (array_keys($builder->findByTag(self::COMMAND_TAG)) as $serviceName) {
			$app->addSetup('add', array('@' . $serviceName));
		}
	}



	/**
	 * @param \Nette\Config\Configurator $configurator
	 */
	public static function register(Nette\Config\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\Config\Compiler $compiler) {
			$compiler->addExtension('console', new ConsoleExtension());
		};
	}

}
