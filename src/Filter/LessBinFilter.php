<?php

namespace WebLoader\Filter;

use WebLoader\Compiler;
use function dirname;
use function pathinfo;
use const PATHINFO_EXTENSION;

/**
 * Less CSS filter
 */
class LessBinFilter
{

	/** @var string */
	private $bin;

	/** @var array */
	private $env;

	/**
	 * @param string $bin
	 * @param array $env
	 */
	public function __construct($bin = 'lessc', array $env = [])
	{
		$this->bin = $bin;
		$this->env = $env + $_ENV;
		unset($this->env['argv'], $this->env['argc']);
	}

	/**
	 * Invoke filter
	 *
	 * @param string $code
	 * @param string $file
	 * @return string
	 */
	public function __invoke($code, Compiler $loader, $file)
	{
		if (pathinfo($file, PATHINFO_EXTENSION) === 'less') {
			$code = Process::run("{$this->bin} -", $code, dirname($file), $this->env);
		}

		return $code;
	}

}
