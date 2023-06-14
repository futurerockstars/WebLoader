<?php

namespace WebLoader\Test;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use WebLoader\FileCollection;
use WebLoader\FileNotFoundException;
use WebLoader\Path;
use function count;
use const DIRECTORY_SEPARATOR;

/**
 * FileCollection test
 */
class FileCollectionTest extends TestCase
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
		$this->object->addFiles([__DIR__ . '/fixtures/c.txt']);
		$expected = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'c.txt',
		];
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
		$expected = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
		];
		self::assertEqualPaths($expected, $this->object->getFiles());

		$this->object->removeFiles([__DIR__ . '/fixtures/b.txt']);
	}

	public function testCanonicalizePath(): void
	{
		$abs = __DIR__ . '/./fixtures/a.txt';
		$rel = 'a.txt';
		$expected = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt';

		self::assertEqualPaths($expected, $this->object->cannonicalizePath($abs));
		self::assertEqualPaths($expected, $this->object->cannonicalizePath($rel));

		try {
			$this->object->cannonicalizePath('nesdagf');
			self::fail('Exception was not thrown.');
		} catch (FileNotFoundException $e) {
			// Noop
		}
	}

	public function testClear(): void
	{
		$this->object->addFile('a.txt');
		$this->object->addRemoteFile('http://jquery.com/jquery.js');
		$this->object->addWatchFile('b.txt');
		$this->object->clear();

		self::assertEquals([], $this->object->getFiles());
		self::assertEquals([], $this->object->getRemoteFiles());
		self::assertEquals([], $this->object->getWatchFiles());
	}

	public function testRemoteFiles(): void
	{
		$this->object->addRemoteFile('http://jquery.com/jquery.js');
		$this->object->addRemoteFiles([
			'http://jquery.com/jquery.js',
			'http://google.com/angular.js',
		]);

		$expected = [
			'http://jquery.com/jquery.js',
			'http://google.com/angular.js',
		];
		self::assertEquals($expected, $this->object->getRemoteFiles());
	}

	public function testWatchFiles(): void
	{
		$this->object->addWatchFile(__DIR__ . '/fixtures/a.txt');
		$this->object->addWatchFile(__DIR__ . '/fixtures/b.txt');
		$this->object->addWatchFiles([__DIR__ . '/fixtures/c.txt']);
		$expected = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'c.txt',
		];
		self::assertEqualPaths($expected, $this->object->getWatchFiles());
	}

	public function testTraversableFiles(): void
	{
		$this->object->addFiles(new ArrayIterator(['a.txt']));
		self::assertEquals(1, count($this->object->getFiles()));
	}

	public function testTraversableRemoteFiles(): void
	{
		$this->object->addRemoteFiles(new ArrayIterator(['http://jquery.com/jquery.js']));
		self::assertEquals(1, count($this->object->getRemoteFiles()));
	}

	public function testSplFileInfo(): void
	{
		$this->object->addFile(new SplFileInfo(__DIR__ . '/fixtures/a.txt'));
		self::assertEquals(1, count($this->object->getFiles()));
	}

	private static function assertEqualPaths($expected, $actual)
	{
		$actual = (array) $actual;
		foreach ((array) $expected as $key => $path) {
			self::assertTrue(isset($actual[$key]));
			self::assertEquals(Path::normalize($path), Path::normalize($actual[$key]));
		}
	}

}
