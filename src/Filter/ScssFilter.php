<?php

namespace WebLoader\Filter;

use Leafo\ScssPhp\Compiler;
use scssc;
use function pathinfo;
use const PATHINFO_DIRNAME;
use const PATHINFO_EXTENSION;

/**
 * Scss CSS filter
 */
class ScssFilter
{

	/** @var Compiler */
	private $sc;

	public function __construct(?Compiler $sc = null)
	{
		$this->sc = $sc;
	}

	/**
	 * @return Compiler|scssc
	 */
	private function getScssC()
	{
		// lazy loading
		if (empty($this->sc)) {
			$this->sc = new Compiler();
		}

		return $this->sc;
	}

	/**
	 * Invoke filter
	 *
	 * @param string $code
	 * @param string $file
	 * @return string
	 */
	public function __invoke($code, \WebLoader\Compiler $loader, $file)
	{
		if (pathinfo($file, PATHINFO_EXTENSION) === 'scss') {
			$this->getScssC()->setImportPaths(['', pathinfo($file, PATHINFO_DIRNAME) . '/']);

			return $this->getScssC()->compile($code);
		}

		return $code;
	}

}
