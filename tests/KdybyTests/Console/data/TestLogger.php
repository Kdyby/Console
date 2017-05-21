<?php

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

	/**
	 * @param string $pattern
	 */
	public function __construct($pattern)
	{
		parent::__construct(NULL, NULL);
		$this->pattern = $pattern;
	}

	public function log($value, $priority = 'info')
	{
		if ($value instanceof \Exception) {
			throw $value;
		}

		$this->messages[] = func_get_args();
		Assert::match('%A?%' . $this->pattern, implode((array) $value));
	}

}
