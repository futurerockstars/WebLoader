<?php

namespace WebLoader\Test\Filter;

use lessc;
use PHPUnit\Framework\TestCase;
use WebLoader\Compiler;
use WebLoader\DefaultOutputNamingConvention;
use WebLoader\FileCollection;
use WebLoader\Filter\LessFilter;
use function file_get_contents;
use function mkdir;

class LessFilterTest extends TestCase
{

	/** @var LessFilter */
	private $filter;

	/** @var Compiler */
	private $compiler;

	protected function setUp(): void
	{
		$this->filter = new LessFilter(new lessc());

		$files = new FileCollection(__DIR__ . '/../fixtures');
		@mkdir($outputDir = __DIR__ . '/../temp/');
		$this->compiler = new Compiler($files, new DefaultOutputNamingConvention(), $outputDir);
	}

	public function testReplace(): void
	{
		$file = __DIR__ . '/../fixtures/style.less';
		$less = ($this->filter)(file_get_contents($file), $this->compiler, $file);
		self::assertSame(file_get_contents(__DIR__ . '/../fixtures/style.less.expected'), $less);
	}

}
