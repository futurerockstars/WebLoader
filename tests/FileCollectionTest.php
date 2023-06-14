<?php

namespace WebLoader\Test;

use WebLoader\FileCollection;
use WebLoader\FileNotFoundException;

/**
 * FileCollection test
 *
 * @author Jan Marek
 */
class FileCollectionTest extends \PHPUnit\Framework\TestCase
{

	/** @var FileCollection */
	private $object;

	protected function setUp(): void
	{
		$this->object = new FileCollection(__DIR__ . '/fixtures');
	}

	public function testAddGetFiles(): void
	{
		$this->object->addFile('a.txt');
		$this->object->addFile(__DIR__ . '/fixtures/a.txt');
		$this->object->addFile(__DIR__ . '/fixtures/b.txt');
		$this->object->addFiles(array(__DIR__ . '/fixtures/c.txt'));
		$expected = array(
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'c.txt',
		);
		self::assertEqualPaths($expected, $this->object->getFiles());
	}

	public function testAddNonExistingFile(): void
	{
		$this->expectException(FileNotFoundException::class);

		$this->object->addFile('sdfsdg.txt');
	}

	public function testRemoveFile(): void
	{
		$this->object->addFile(__DIR__ . '/fixtures/a.txt');
		$this->object->addFile(__DIR__ . '/fixtures/b.txt');

		$this->object->removeFile('a.txt');
		$expected = array(
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
		);
		self::assertEqualPaths($expected, $this->object->getFiles());

		$this->object->removeFiles(array(__DIR__ . '/fixtures/b.txt'));
	}

	public function testCannonicalizePath(): void
	{
		$abs = __DIR__ . '/./fixtures/a.txt';
		$rel = 'a.txt';
		$expected = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt';

		self::assertEqualPaths($expected, $this->object->cannonicalizePath($abs));
		self::assertEqualPaths($expected, $this->object->cannonicalizePath($rel));

		try {
			$this->object->cannonicalizePath('nesdagf');
			self::fail('Exception was not thrown.');
		} catch (\WebLoader\FileNotFoundException $e) {
		}
	}

	public function testClear(): void
	{
		$this->object->addFile('a.txt');
		$this->object->addRemoteFile('http://jquery.com/jquery.js');
		$this->object->addWatchFile('b.txt');
		$this->object->clear();

		self::assertEquals(array(), $this->object->getFiles());
		self::assertEquals(array(), $this->object->getRemoteFiles());
		self::assertEquals(array(), $this->object->getWatchFiles());
	}

	public function testRemoteFiles(): void
	{
		$this->object->addRemoteFile('http://jquery.com/jquery.js');
		$this->object->addRemoteFiles(array(
			'http://jquery.com/jquery.js',
			'http://google.com/angular.js',
		));

		$expected = array(
			'http://jquery.com/jquery.js',
			'http://google.com/angular.js',
		);
		self::assertEquals($expected, $this->object->getRemoteFiles());
	}

	public function testWatchFiles(): void
	{
		$this->object->addWatchFile(__DIR__ . '/fixtures/a.txt');
		$this->object->addWatchFile(__DIR__ . '/fixtures/b.txt');
		$this->object->addWatchFiles(array(__DIR__ . '/fixtures/c.txt'));
		$expected = array(
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'c.txt',
		);
		self::assertEqualPaths($expected, $this->object->getWatchFiles());
	}

	public function testTraversableFiles(): void
	{
		$this->object->addFiles(new \ArrayIterator(array('a.txt')));
		self::assertEquals(1, count($this->object->getFiles()));
	}

	public function testTraversableRemoteFiles(): void
	{
		$this->object->addRemoteFiles(new \ArrayIterator(array('http://jquery.com/jquery.js')));
		self::assertEquals(1, count($this->object->getRemoteFiles()));
	}

	public function testSplFileInfo(): void
	{
		$this->object->addFile(new \SplFileInfo(__DIR__ . '/fixtures/a.txt'));
		self::assertEquals(1, count($this->object->getFiles()));
	}

	private static function assertEqualPaths($expected, $actual)
	{
		$actual = (array) $actual;
		foreach ((array) $expected as $key => $path) {
			self::assertTrue(isset($actual[$key]));
			self::assertEquals(\WebLoader\Path::normalize($path), \WebLoader\Path::normalize($actual[$key]));
		}
	}

}
