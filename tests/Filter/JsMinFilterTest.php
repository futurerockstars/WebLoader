<?php declare(strict_types = 1);

namespace Filter;

use PHPUnit\Framework\TestCase;
use WebLoader\Compiler;
use WebLoader\DefaultOutputNamingConvention;
use WebLoader\FileCollection;
use WebLoader\Filter\JsMinFilter;
use function file_get_contents;
use function mkdir;

class JsMinFilterTest extends TestCase
{

	private JsMinFilter $filter;

	private Compiler $compiler;

	protected function setUp(): void
	{
		$this->filter = new JsMinFilter();

		$files = new FileCollection(__DIR__ . '/../fixtures');
		@mkdir($outputDir = __DIR__ . '/../temp/');
		$this->compiler = new Compiler($files, new DefaultOutputNamingConvention(), $outputDir);
	}

	public function testMinify(): void
	{
		$file = __DIR__ . '/../fixtures/jsmin.js';
		$minified = ($this->filter)(
			file_get_contents($file),
			$this->compiler,
			$file,
		);

		self::assertSame(file_get_contents(__DIR__ . '/../fixtures/jsmin.js.expected'), $minified . "\n");
	}

}
