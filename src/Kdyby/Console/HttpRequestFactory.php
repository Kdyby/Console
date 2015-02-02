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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HttpRequestFactory extends Nette\Http\RequestFactory
{

	/**
	 * @var Nette\Http\UrlScript
	 */
	private $fakeUrl;



	/**
	 * @param string|Nette\Http\UrlScript $url
	 */
	public function setFakeRequestUrl($url)
	{
		$this->fakeUrl = $url ? new Nette\Http\UrlScript($url) : NULL;
	}



	/**
	 * @return \Nette\Http\Request
	 */
	public function createHttpRequest()
	{
		if ($this->fakeUrl === NULL || PHP_SAPI !== 'cli' || !empty($_SERVER['REMOTE_HOST'])) {
			return parent::createHttpRequest();
		}

		return new Nette\Http\Request($this->fakeUrl, NULL, array(), array(), array(), array(), PHP_SAPI, '127.0.0.1', '127.0.0.1');
	}

}
