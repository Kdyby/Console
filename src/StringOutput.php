<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Console;

class StringOutput extends \Symfony\Component\Console\Output\Output
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var string
	 */
	private $output = '';

	/**
	 * @param string $message A message to write to the output
	 * @param bool $newline Whether to add a newline or not
	 */
	protected function doWrite($message, $newline): void
	{
		$this->output .= $message . ($newline ? "\n" : '');
	}

	public function getOutput(): string
	{
		return $this->output;
	}

}
