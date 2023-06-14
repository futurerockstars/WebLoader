<?php

namespace WebLoader;

interface IFileCollection
{

	/**
	 * @return string
	 */
	public function getRoot();

	/**
	 * @return array
	 */
	public function getFiles();

	/**
	 * @return array
	 */
	public function getRemoteFiles();

	/**
	 * @return array
	 */
	public function getWatchFiles();

}
