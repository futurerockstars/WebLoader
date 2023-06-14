<?php

namespace WebLoader\Filter;

use RuntimeException;
use WebLoader\Compiler;
use WebLoader\WebLoaderException;
use function pathinfo;
use const PATHINFO_DIRNAME;
use const PATHINFO_EXTENSION;

/**
 * Stylus filter
 */
class StylusFilter
{

	/** @var string */
	private $bin;

	/** @var bool */
	public $compress = false;

	/** @var bool */
	public $includeCss = false;

	public function __construct($bin = 'stylus')
	{
		$this->bin = $bin;
	}

	/**
	 * Invoke filter
	 *
	 * @param string $code
	 * @param string|null $file
	 * @return string
	 */
	public function __invoke($code, Compiler $loader, $file = null)
	{
		if (pathinfo($file, PATHINFO_EXTENSION) === 'styl') {
			$cmd = $this->bin . ($this->compress ? ' -c' : '') . ($this->includeCss ? ' --include-css' : '') . ' -I ' . pathinfo(
				$file,
				PATHINFO_DIRNAME,
			);
			try {
				$code = Process::run($cmd, $code);
			} catch (RuntimeException $e) {
				throw new WebLoaderException('Stylus Filter Error', 0, $e);
			}
		}

		return $code;
	}

}
