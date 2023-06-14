<?php

namespace WebLoader\Nette;

use Nette\DI\Container;
use Nette\Http\IRequest;
use WebLoader\Compiler;
use function rtrim;
use function strtolower;
use function ucfirst;

class LoaderFactory
{

	/** @var IRequest */
	private $httpRequest;

	/** @var Container */
	private $serviceLocator;

	/** @var array */
	private $tempPaths;

	/** @var string */
	private $extensionName;

	/**
	 * @param array $tempPaths
	 * @param string $extensionName
	 */
	public function __construct(array $tempPaths, $extensionName, IRequest $httpRequest, Container $serviceLocator)
	{
		$this->httpRequest = $httpRequest;
		$this->serviceLocator = $serviceLocator;
		$this->tempPaths = $tempPaths;
		$this->extensionName = $extensionName;
	}

	/**
	 * @param string $name
	 * @return CssLoader
	 */
	public function createCssLoader($name)
	{
		/** @var Compiler $compiler */
		$compiler = $this->serviceLocator->getService($this->extensionName . '.css' . ucfirst($name) . 'Compiler');

		return new CssLoader($compiler, $this->formatTempPath($name));
	}

	/**
	 * @param string $name
	 * @return JavaScriptLoader
	 */
	public function createJavaScriptLoader($name)
	{
		/** @var Compiler $compiler */
		$compiler = $this->serviceLocator->getService($this->extensionName . '.js' . ucfirst($name) . 'Compiler');

		return new JavaScriptLoader($compiler, $this->formatTempPath($name));
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function formatTempPath($name)
	{
		$lName = strtolower($name);
		$tempPath = $this->tempPaths[$lName] ?? Extension::DEFAULT_TEMP_PATH;

		return rtrim($this->httpRequest->getUrl()->basePath, '/') . '/' . $tempPath;
	}

}
