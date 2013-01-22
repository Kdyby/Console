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
		'commands' => array()
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('helperSet'))
			->setClass('Symfony\Component\Console\Helper\HelperSet', array(array(
				new Nette\DI\Statement('Symfony\Component\Console\Helper\DialogHelper'),
				new Nette\DI\Statement('Symfony\Component\Console\Helper\FormatterHelper'),
			)))
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('application'))
			->setClass('Kdyby\Console\Application', array($config['name'], $config['version']))
			->addSetup('setHelperSet', array($this->prefix('@helperSet')))
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('router'))
			->setClass('Kdyby\Console\CliRouter')
			->setAutowired(FALSE)
			->setInject(FALSE);

		$builder->getDefinition('router')
			->addSetup('offsetSet', array(NULL, $this->prefix('@router')));

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
			$def->setInject(FALSE);
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

