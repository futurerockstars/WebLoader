<?php

namespace WebLoader\Test\Filter;

use WebLoader\Filter\CssUrlsFilter;

class CssUrlsFilterTest extends \PHPUnit\Framework\TestCase
{

	/** @var CssUrlsFilter */
	private $object;

	protected function setUp(): void
	{
		$this->object = new CssUrlsFilter(__DIR__ . '/..', '/');
	}

	public function testCannonicalizePath(): void
	{
		$path = $this->object->cannonicalizePath('/prase/./dobytek/../ale/nic.jpg');
		self::assertEquals('/prase/ale/nic.jpg', $path);
	}

	public function testAbsolutizeAbsolutized(): void
	{
		$cssPath = __DIR__ . '/../fixtures/style.css';

		$url = 'http://image.com/image.jpg';
		self::assertEquals($url, $this->object->absolutizeUrl($url, '\'', $cssPath));

		$abs = '/images/img.png';
		self::assertEquals($abs, $this->object->absolutizeUrl($abs, '\'', $cssPath));
	}

	public function testAbsolutize(): void
	{
		$cssPath = __DIR__ . '/../fixtures/style.css';

		self::assertEquals(
			'/images/image.png',
			$this->object->absolutizeUrl('./../images/image.png', '\'', $cssPath)
		);

		self::assertEquals(
			'/images/path/to/image.png',
			$this->object->absolutizeUrl('./../images/path/./to/image.png', '\'', $cssPath)
		);
	}

	public function testAbsolutizeOutsideOfDocRoot(): void
	{
		$path = './../images/image.png';
		$existingPath = __DIR__ . '/../../Compiler.php';
		self::assertEquals($path, $this->object->absolutizeUrl($path, '\'', $existingPath));
	}

}
