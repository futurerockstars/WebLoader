<?php

namespace WebLoader;

use Traversable;
use function array_diff;
use function array_map;
use function array_values;
use function file_exists;
use function in_array;

/**
 * FileCollection
 */
class FileCollection implements IFileCollection
{

	/** @var string */
	private $root;

	/** @var array */
	private $files = [];

	/** @var array */
	private $watchFiles = [];

	/** @var array */
	private $remoteFiles = [];

	/**
	 * @param string|null $root files root for relative paths
	 */
	public function __construct($root = null)
	{
		$this->root = $root;
	}

	/**
	 * Get file list
	 *
	 * @return array
	 */
	public function getFiles()
	{
		return array_values($this->files);
	}

	/**
	 * Make path absolute
	 *
	 * @param string $path
	 * @return string
	 * @throws FileNotFoundException
	 */
	public function cannonicalizePath($path)
	{
		$rel = Path::normalize($this->root . '/' . $path);
		if (file_exists($rel)) {
			return $rel;
		}

		$abs = Path::normalize($path);
		if (file_exists($abs)) {
			return $abs;
		}

		throw new FileNotFoundException("File '$path' does not exist.");
	}

	/**
	 * Add file
	 *
	 * @param string $file
	 */
	public function addFile($file)
	{
		$file = $this->cannonicalizePath((string) $file);

		if (in_array($file, $this->files, true)) {
			return;
		}

		$this->files[] = $file;
	}

	/**
	 * Add files
	 *
	 * @param array|Traversable $files array list of files
	 */
	public function addFiles($files)
	{
		foreach ($files as $file) {
			$this->addFile($file);
		}
	}

	/**
	 * @param string $file
	 */
	public function removeFile($file)
	{
		$this->removeFiles([$file]);
	}

	/**
	 * @param array $files list of files
	 */
	public function removeFiles(array $files)
	{
		$files = array_map([$this, 'cannonicalizePath'], $files);
		$this->files = array_diff($this->files, $files);
	}

	/**
	 * Add file in remote repository (for example Google CDN).
	 *
	 * @param string $file URL address
	 */
	public function addRemoteFile($file)
	{
		if (in_array($file, $this->remoteFiles)) {
			return;
		}

		$this->remoteFiles[] = $file;
	}

	/**
	 * Add multiple remote files
	 *
	 * @param array|Traversable $files
	 */
	public function addRemoteFiles($files)
	{
		foreach ($files as $file) {
			$this->addRemoteFile($file);
		}
	}

	/**
	 * Remove all files
	 */
	public function clear()
	{
		$this->files = [];
		$this->watchFiles = [];
		$this->remoteFiles = [];
	}

	/**
	 * @return array
	 */
	public function getRemoteFiles()
	{
		return $this->remoteFiles;
	}

	/**
	 * @return string
	 */
	public function getRoot()
	{
		return $this->root;
	}

	/**
	 * @param string $file
	 */
	public function addWatchFile($file)
	{
		$file = $this->cannonicalizePath((string) $file);

		if (in_array($file, $this->watchFiles, true)) {
			return;
		}

		$this->watchFiles[] = $file;
	}

	/**
	 * @param array|Traversable $files array list of files
	 */
	public function addWatchFiles($files)
	{
		foreach ($files as $file) {
			$this->addWatchFile($file);
		}
	}

	/**
	 * Get watch file list
	 *
	 * @return array
	 */
	public function getWatchFiles()
	{
		return array_values($this->watchFiles);
	}

}
