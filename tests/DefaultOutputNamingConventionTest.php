<?php

namespace WebLoader\Test;

use Mockery;
use PHPUnit\Framework\TestCase;
use WebLoader\DefaultOutputNamingConvention;
use const DIRECTORY_SEPARATOR;

/**
 * DefaultOutputNamingConvention test
 */
class DefaultOutputNamingConventionTest extends TestCase
{

	/** @var DefaultOutputNamingConvention */
	private $object;

	private $compiler;

	protected function setUp(): void
	{
		$this->object = new DefaultOutputNamingConvention();
		$this->compiler = Mockery::mock('Webloader\Compiler');
	}

	public function testMultipleFiles(): void
	{
		$files = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'b.txt',
		];

		$name = $this->object->getFilename($files, $this->compiler);
		self::assertMatchesRegularExpression('/^webloader-[0-9a-f]{12}$/', $name);

		// another hash
		$files[] = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'c.txt';
		$name2 = $this->object->getFilename($files, $this->compiler);
		self::assertNotEquals($name, $name2, 'Different file lists results to same filename.');
	}

	public function testOneFile(): void
	{
		$files = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
		];

		$name = $this->object->getFilename($files, $this->compiler);
		self::assertMatchesRegularExpression('/^webloader-[0-9a-f]{12}-a$/', $name);
	}

	public function testCssConvention(): void
	{
		$files = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
		];

		$name = DefaultOutputNamingConvention::createCssConvention()->getFilename($files, $this->compiler);
		self::assertMatchesRegularExpression('/^cssloader-[0-9a-f]{12}-a.css$/', $name);
	}

	public function testJsConvention(): void
	{
		$files = [
			__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'a.txt',
		];

		$name = DefaultOutputNamingConvention::createJsConvention()->getFilename($files, $this->compiler);
		self::assertMatchesRegularExpression('/^jsloader-[0-9a-f]{12}-a.js$/', $name);
	}

}
