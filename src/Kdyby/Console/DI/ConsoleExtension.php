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



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

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
		'name' => Nette\Framework::NAME,
		'version' => Nette\Framework::VERSION,
		'commands' => array(),
		'url' => NULL,
		'disabled' => TRUE,
	);



	public function __construct()
	{
		$this->defaults['disabled'] = PHP_SAPI !== 'cli';
	}



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if ($config['disabled']) {
			return;
		}

		$builder->addDefinition($this->prefix('helperSet'))
			->setClass('Symfony\Component\Console\Helper\HelperSet', array(array(
				new Nette\DI\Statement('Symfony\Component\Console\Helper\DialogHelper'),
				new Nette\DI\Statement('Symfony\Component\Console\Helper\FormatterHelper'),
				new Nette\DI\Statement('Symfony\Component\Console\Helper\ProgressHelper'),
				new Nette\DI\Statement('Kdyby\Console\Helpers\PresenterHelper'),
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
				->addSetup('setFakeRequestUrl', array($config['url']));
		}

		$builder->addDefinition($this->prefix('dicHelper'))
			->setClass('Kdyby\Console\ContainerHelper')
			->addTag(self::HELPER_TAG, 'dic');

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
