<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

use Kdyby;
use Nette;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HttpRequestFactory extends Nette\Http\RequestFactory
{

	/**
	 * @var \Nette\Http\UrlScript|NULL
	 */
	private $fakeUrl;



	/**
	 * @param string|\Nette\Http\UrlScript $url
	 * @param string|null $scriptPath
	 */
	public function setFakeRequestUrl($url, $scriptPath = null)
	{
		$this->fakeUrl = $url ? new UrlScript($url) : NULL;
		if ($scriptPath !== null) {
			if ($this->fakeUrl === NULL) {
				throw new \Kdyby\Console\InvalidArgumentException('When the $scriptPath is specified, the $url must be also specified.');
			}
			$this->fakeUrl->setScriptPath($scriptPath);
		}
	}



	/**
	 * @return \Nette\Http\Request
	 */
	public function createHttpRequest()
	{
		if ($this->fakeUrl === NULL || PHP_SAPI !== 'cli' || !empty($_SERVER['REMOTE_HOST'])) {
			return parent::createHttpRequest();
		}

		return new HttpRequest($this->fakeUrl, NULL, [], [], [], [], PHP_SAPI, '127.0.0.1', '127.0.0.1');
	}

}
