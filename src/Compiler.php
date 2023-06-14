<?php

namespace WebLoader;

use Nette\Utils\FileSystem;
use function array_merge;
use function array_unique;
use function call_user_func;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function in_array;
use function is_callable;
use function max;
use function stream_get_wrappers;
use const PHP_EOL;

/**
 * Compiler
 */
class Compiler
{

	/** @var string */
	private $outputDir;

	/** @var bool */
	private $joinFiles = true;

	/** @var array */
	private $filters = [];

	/** @var array */
	private $fileFilters = [];

	/** @var IFileCollection */
	private $collection;

	/** @var IOutputNamingConvention */
	private $namingConvention;

	/** @var bool */
	private $checkLastModified = true;

	/** @var bool */
	private $debugging = false;

	public function __construct(IFileCollection $files, IOutputNamingConvention $convention, $outputDir)
	{
		$this->collection = $files;
		$this->namingConvention = $convention;
		$this->setOutputDir($outputDir);
	}

	/**
	 * Create compiler with predefined css output naming convention
	 *
	 * @param string $outputDir
	 * @return Compiler
	 */
	public static function createCssCompiler(IFileCollection $files, $outputDir)
	{
		return new static($files, DefaultOutputNamingConvention::createCssConvention(), $outputDir);
	}

	/**
	 * Create compiler with predefined javascript output naming convention
	 *
	 * @param string $outputDir
	 * @return Compiler
	 */
	public static function createJsCompiler(IFileCollection $files, $outputDir)
	{
		return new static($files, DefaultOutputNamingConvention::createJsConvention(), $outputDir);
	}

	/**
	 * @param bool $allow
	 */
	public function enableDebugging($allow = true)
	{
		$this->debugging = (bool) $allow;
	}

	/**
	 * Get temp path
	 *
	 * @return string
	 */
	public function getOutputDir()
	{
		return $this->outputDir;
	}

	/**
	 * Set temp path
	 *
	 * @param string $tempPath
	 */
	public function setOutputDir($tempPath)
	{
		FileSystem::createDir($tempPath);

		$this->outputDir = $tempPath;
	}

	/**
	 * Get join files
	 *
	 * @return bool
	 */
	public function getJoinFiles()
	{
		return $this->joinFiles;
	}

	/**
	 * Set join files
	 *
	 * @param bool $joinFiles
	 */
	public function setJoinFiles($joinFiles)
	{
		$this->joinFiles = (bool) $joinFiles;
	}

	/**
	 * Set check last modified
	 *
	 * @param bool $checkLastModified
	 */
	public function setCheckLastModified($checkLastModified)
	{
		$this->checkLastModified = (bool) $checkLastModified;
	}

	/**
	 * Get last modified timestamp of newest file
	 *
	 * @param array $files
	 * @return int
	 */
	public function getLastModified(?array $files = null)
	{
		if ($files === null) {
			$files = $this->collection->getFiles();
		}

		$modified = 0;

		foreach ($files as $file) {
			$modified = max($modified, filemtime($file));
		}

		return $modified;
	}

	/**
	 * Get joined content of all files
	 *
	 * @param array $files
	 * @return string
	 */
	public function getContent(?array $files = null)
	{
		if ($files === null) {
			$files = $this->collection->getFiles();
		}

		// load content
		$content = '';
		foreach ($files as $file) {
			$content .= PHP_EOL . $this->loadFile($file);
		}

		// apply filters
		foreach ($this->filters as $filter) {
			$content = call_user_func($filter, $content, $this);
		}

		return $content;
	}

	/**
	 * Load content and save file
	 *
	 * @param bool $ifModified
	 * @return array filenames of generated files
	 */
	public function generate($ifModified = true)
	{
		$files = $this->collection->getFiles();

		if (!count($files)) {
			return [];
		}

		if ($this->joinFiles) {
			$watchFiles = $this->checkLastModified
				? array_unique(array_merge($files, $this->collection->getWatchFiles()))
				: [];

			return [
				$this->generateFiles($files, $ifModified, $watchFiles),
			];
		} else {
			$arr = [];

			foreach ($files as $file) {
				$watchFiles = $this->checkLastModified
					? array_unique(
						array_merge([$file], $this->collection->getWatchFiles()),
					)
					: [];
				$arr[] = $this->generateFiles([$file], $ifModified, $watchFiles);
			}

			return $arr;
		}
	}

	protected function generateFiles(array $files, $ifModified, array $watchFiles = [])
	{
		$name = $this->namingConvention->getFilename($files, $this);
		$path = $this->outputDir . '/' . $name;
		$lastModified = $this->checkLastModified ? $this->getLastModified($watchFiles) : 0;

		if (!$ifModified || !file_exists($path) || $lastModified > filemtime($path) || $this->debugging === true) {
			$outPath = in_array('nette.safe', stream_get_wrappers()) ? 'nette.safe://' . $path : $path;
			file_put_contents($outPath, $this->getContent($files));
		}

		return (object) [
			'file' => $name,
			'lastModified' => $lastModified,
			'sourceFiles' => $files,
		];
	}

	/**
	 * Load file
	 *
	 * @param string $file path
	 * @return string
	 */
	protected function loadFile($file)
	{
		$content = file_get_contents($file);

		foreach ($this->fileFilters as $filter) {
			$content = call_user_func($filter, $content, $this, $file);
		}

		return $content;
	}

	/**
	 * @return IFileCollection
	 */
	public function getFileCollection()
	{
		return $this->collection;
	}

	/**
	 * @return IOutputNamingConvention
	 */
	public function getOutputNamingConvention()
	{
		return $this->namingConvention;
	}

	public function setFileCollection(IFileCollection $collection)
	{
		$this->collection = $collection;
	}

	public function setOutputNamingConvention(IOutputNamingConvention $namingConvention)
	{
		$this->namingConvention = $namingConvention;
	}

	/**
	 * @param callback $filter
	 * @throws InvalidArgumentException
	 */
	public function addFilter($filter)
	{
		if (!is_callable($filter)) {
			throw new InvalidArgumentException('Filter is not callable.');
		}

		$this->filters[] = $filter;
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * @param callback $filter
	 * @throws InvalidArgumentException
	 */
	public function addFileFilter($filter)
	{
		if (!is_callable($filter)) {
			throw new InvalidArgumentException('Filter is not callable.');
		}

		$this->fileFilters[] = $filter;
	}

	/**
	 * @return array
	 */
	public function getFileFilters()
	{
		return $this->fileFilters;
	}

}
