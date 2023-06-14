<?php

namespace WebLoader\Filter;

use ScssPhp\ScssPhp\Compiler;
use function pathinfo;
use const PATHINFO_DIRNAME;
use const PATHINFO_EXTENSION;

class ScssFilter
{

	private Compiler $sc;

	public function __construct(?Compiler $sc = null)
	{
		$this->sc = $sc ?? new Compiler();
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
			$this->sc->setImportPaths(['', pathinfo($file, PATHINFO_DIRNAME) . '/']);

			return $this->sc->compileString($code)->getCss();
		}

		return $code;
	}

}
