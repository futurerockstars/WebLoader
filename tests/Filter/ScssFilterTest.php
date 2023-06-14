<?php

namespace WebLoader\Test\Filter;

use PHPUnit\Framework\TestCase;
use WebLoader\Compiler;
use WebLoader\DefaultOutputNamingConvention;
use WebLoader\FileCollection;
use WebLoader\Filter\ScssFilter;
use WebLoader\Filter\VariablesFilter;
use function file_get_contents;
use function mkdir;
use function realpath;

class ScssFilterTest extends TestCase
{

	/** @var ScssFilter */
	private $filter;

	/** @var Compiler */
	private $compiler;

	protected function setUp(): void
	{
		$this->filter = new ScssFilter(new \ScssPhp\ScssPhp\Compiler());

		$files = new FileCollection(__DIR__ . '/../fixtures');
		@mkdir($outputDir = __DIR__ . '/../temp/');
		$this->compiler = new Compiler($files, new DefaultOutputNamingConvention(), $outputDir);
	}

	public function testReplace(): void
	{
		$file = __DIR__ . '/../fixtures/style.scss';
		$less = ($this->filter)(file_get_contents($file), $this->compiler, $file);
		self::assertSame(file_get_contents(__DIR__ . '/../fixtures/style.scss.expected'), $less);
	}

	public function testImportAbsolutePath(): void
	{
		$file = __DIR__ . '/../fixtures/styleAbsolute.scss';
		$filter = new VariablesFilter([
			'fixturesAbsolutePath' => realpath(__DIR__ . '/../fixtures'),
		]);
		$code = file_get_contents($file);
		$filtered = $filter($code);
		$less = ($this->filter)($filtered, $this->compiler, $file);
		self::assertSame(file_get_contents(__DIR__ . '/../fixtures/styleAbsolute.scss.expected'), $less);
	}

}
