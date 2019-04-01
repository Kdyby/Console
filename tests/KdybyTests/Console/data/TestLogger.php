<?php

declare(strict_types = 1);

namespace KdybyTests\Console;

use Tester\Assert;

class TestLogger extends \Tracy\Logger
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var mixed[]
	 */
	public $messages = [];

	/**
	 * @var string
	 */
	private $pattern;

	public function __construct(string $pattern)
	{
		parent::__construct(NULL, NULL);
		$this->pattern = $pattern;
	}

	/**
	 * @param mixed $value
	 * @param string $priority
	 * @throws \Exception
	 */
	public function log($value, $priority = 'info'): void
	{
		if ($value instanceof \Exception) {
			throw $value;
		}

		$this->messages[] = func_get_args();
		Assert::match('%A?%' . $this->pattern, implode((array) $value));
	}

}
