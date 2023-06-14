<?php

namespace WebLoader\Filter;

use WebLoader\Compiler;
use function escapeshellarg;
use function file_get_contents;
use function pathinfo;
use function sprintf;
use function substr_replace;
use const PATHINFO_EXTENSION;

/**
 * TypeScript filter
 */
class TypeScriptFilter
{

	/** @var string */
	private $bin;

	/** @var array */
	private $env;

	/**
	 * @param string $bin
	 * @param array $env
	 */
	public function __construct($bin = 'tsc', array $env = [])
	{
		$this->bin = $bin;
		$this->env = $env + $_ENV;
		unset($this->env['argv'], $this->env['argc']);
	}

	/**
	 * Invoke filter
	 *
	 * @param  string $code
	 * @param  string $file
	 * @return string
	 */
	public function __invoke($code, Compiler $compiler, $file = null)
	{
		if (pathinfo($file, PATHINFO_EXTENSION) === 'ts') {
			$out = substr_replace($file, 'js', -2);
			$cmd = sprintf('%s %s --target ES5 --out %s', $this->bin, escapeshellarg($file), escapeshellarg($out));
			Process::run($cmd, null, null, $this->env);
			$code = file_get_contents($out);
		}

		return $code;
	}

}
