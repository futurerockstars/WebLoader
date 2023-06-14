<?php

namespace WebLoader\Filter;

use lessc;
use WebLoader\Compiler;
use function pathinfo;
use const PATHINFO_DIRNAME;
use const PATHINFO_EXTENSION;

/**
 * Less CSS filter
 */
class LessFilter
{

	private $lc;

	public function __construct(?lessc $lc = null)
	{
		$this->lc = $lc;
	}

	/**
	 * @return lessc
	 */
	private function getLessC()
	{
		// lazy loading
		if (empty($this->lc)) {
			$this->lc = new lessc();
		}

		return clone $this->lc;
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
			$lessc = $this->getLessC();
			$lessc->importDir = pathinfo($file, PATHINFO_DIRNAME) . '/';

			return $lessc->compile($code);
		}

		return $code;
	}

}
