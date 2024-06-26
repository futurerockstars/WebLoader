<?php

namespace WebLoader\Filter;

use WebLoader\Compiler;
use WebLoader\InvalidArgumentException;
use WebLoader\Path;
use function addslashes;
use function array_pop;
use function dirname;
use function explode;
use function implode;
use function is_dir;
use function preg_match;
use function preg_replace_callback;
use function strlen;
use function strncmp;
use function strtr;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Absolutize urls in CSS
 */
class CssUrlsFilter
{

	/** @var string */
	private $docRoot;

	/** @var string */
	protected $basePath;

	/**
	 * @param string $docRoot web document root
	 * @param string $basePath base path
	 * @throws InvalidArgumentException
	 */
	public function __construct($docRoot, $basePath = '/')
	{
		$this->docRoot = Path::normalize($docRoot);

		if (!is_dir($this->docRoot)) {
			throw new InvalidArgumentException('Given document root is not directory.');
		}

		$this->basePath = $basePath;
	}

	/**
	 * @param string $basePath
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
	}

	/**
	 * Make relative url absolute
	 *
	 * @param string $url image url
	 * @param string $quote single or double quote
	 * @param string $cssFile absolute css file path
	 * @return string
	 */
	public function absolutizeUrl($url, $quote, $cssFile)
	{
		// is already absolute
		if (preg_match('/^([a-z]+:\/)?\//', $url)) {
			return $url;
		}

		$cssFile = Path::normalize($cssFile);

		// inside document root
		if (strncmp($cssFile, $this->docRoot, strlen($this->docRoot)) === 0) {
			$path = $this->basePath . substr(dirname($cssFile), strlen($this->docRoot)) . DIRECTORY_SEPARATOR . $url;
		} else {
			// outside document root we don't know
			return $url;
		}

		$path = $this->cannonicalizePath($path);

		return $quote === '"' ? addslashes($path) : $path;
	}

	/**
	 * Cannonicalize path
	 *
	 * @param string $path
	 * @return string path
	 */
	public function cannonicalizePath($path)
	{
		$path = strtr($path, DIRECTORY_SEPARATOR, '/');

		$pathArr = [];
		foreach (explode('/', $path) as $i => $name) {
			if ($name === '.' || ($name === '' && $i > 0)) {
				continue;
			}

			if ($name === '..') {
				array_pop($pathArr);

				continue;
			}

			$pathArr[] = $name;
		}

		return implode('/', $pathArr);
	}

	/**
	 * Invoke filter
	 *
	 * @param string $code
	 * @param string $file
	 * @return string
	 */
	public function __invoke($code, Compiler $loader, $file = null)
	{
		// thanks to kravco
		$regexp = '~
			(?<![a-z])
			url\(                                     ## url(
				\s*                                   ##   optional whitespace
				([\'"])?                              ##   optional single/double quote
				(?!data:)                             ##   keep data URIs
				(   (?: (?:\\\\.)+                    ##     escape sequences
					|   [^\'"\\\\,()\s]+              ##     safe characters
					|   (?(1)   (?!\1)[\'"\\\\,() \t] ##       allowed special characters
						|       ^                     ##       (none, if not quoted)
						)
					)*                                ##     (greedy match)
				)
				(?(1)\1)                              ##   optional single/double quote
				\s*                                   ##   optional whitespace
			\)                                        ## )
		~xs';

		$self = $this;

		return preg_replace_callback(
			$regexp,
			fn ($matches) => "url('" . $self->absolutizeUrl($matches[2], $matches[1], $file) . "')",
			$code,
		);
	}

}
