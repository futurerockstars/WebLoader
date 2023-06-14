<?php

namespace WebLoader\Nette;

use Nette\Application\UI\Control;
use Nette\Utils\Html;
use WebLoader\Compiler;
use WebLoader\FileCollection;
use function func_get_args;
use function func_num_args;
use const PHP_EOL;

/**
 * Web loader
 */
abstract class WebLoader extends Control
{

	/** @var Compiler */
	private $compiler;

	/** @var string */
	private $tempPath;

	public function __construct(Compiler $compiler, $tempPath)
	{
		$this->compiler = $compiler;
		$this->tempPath = $tempPath;
	}

	/**
	 * @return Compiler
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}

	public function setCompiler(Compiler $compiler)
	{
		$this->compiler = $compiler;
	}

	/**
	 * @return string
	 */
	public function getTempPath()
	{
		return $this->tempPath;
	}

	public function setTempPath($tempPath)
	{
		$this->tempPath = $tempPath;
	}

	/**
	 * Get html element including generated content
	 *
	 * @param string $source
	 * @return Html
	 */
	abstract public function getElement($source);

	/**
	 * Generate compiled file(s) and render link(s)
	 */
	public function render()
	{
		$hasArgs = func_num_args() > 0;

		if ($hasArgs) {
			$backup = $this->compiler->getFileCollection();
			$newFiles = new FileCollection($backup->getRoot());
			$newFiles->addFiles(func_get_args());
			$this->compiler->setFileCollection($newFiles);
		}

		// remote files
		foreach ($this->compiler->getFileCollection()->getRemoteFiles() as $file) {
			echo $this->getElement($file), PHP_EOL;
		}

		foreach ($this->compiler->generate() as $file) {
			echo $this->getElement($this->getGeneratedFilePath($file)), PHP_EOL;
		}

		if ($hasArgs) {
			$this->compiler->setFileCollection($backup);
		}
	}

	protected function getGeneratedFilePath($file)
	{
		return $this->tempPath . '/' . $file->file . '?' . $file->lastModified;
	}

}
