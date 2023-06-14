<?php

namespace WebLoader\Test;

use Mockery;
use WebLoader\Compiler;
use WebLoader\InvalidArgumentException;

/**
 * CompilerTest
 *
 * @author Jan Marek
 */
class CompilerTest extends \PHPUnit\Framework\TestCase
{

	/** @var \WebLoader\Compiler */
	private $object;

	protected function setUp(): void
	{
		$fileCollection = Mockery::mock('WebLoader\IFileCollection');
		$fileCollection->shouldReceive('getFiles')->andReturn(array(
			__DIR__ . '/fixtures/a.txt',
			__DIR__ . '/fixtures/b.txt',
			__DIR__ . '/fixtures/c.txt',
		));
		$fileCollection->shouldReceive('getWatchFiles')->andReturn(array(
			__DIR__ . '/fixtures/a.txt',
			__DIR__ . '/fixtures/b.txt',
			__DIR__ . '/fixtures/c.txt',
		));

		$convention = Mockery::mock('WebLoader\IOutputNamingConvention');
		$convention->shouldReceive('getFilename')->andReturnUsing(function ($files, $compiler) {
			return 'webloader-' . md5(join(',', $files));
		});

		$this->object = new Compiler($fileCollection, $convention, __DIR__ . '/temp');

		foreach ($this->getTempFiles() as $file) {
			unlink($file);
		}
	}

	/**
	 * @return array
	 */
	private function getTempFiles()
	{
		return glob(__DIR__ . '/temp/webloader-*');
	}

	public function testJoinFiles(): void
	{
		self::assertTrue($this->object->getJoinFiles());

		$ret = $this->object->generate();
		self::assertEquals(1, count($ret), 'Multiple files are generated instead of join.');
		self::assertEquals(1, count($this->getTempFiles()), 'Multiple files are generated instead of join.');
	}

	public function testEmptyFiles(): void
	{
		self::assertTrue($this->object->getJoinFiles());
		$this->object->setFileCollection(new \WebLoader\FileCollection());

		$ret = $this->object->generate();
		self::assertEquals(0, count($ret));
		self::assertEquals(0, count($this->getTempFiles()));
	}

	public function testNotJoinFiles(): void
	{
		$this->object->setJoinFiles(FALSE);
		self::assertFalse($this->object->getJoinFiles());

		$ret = $this->object->generate();
		self::assertEquals(3, count($ret), 'Wrong file count generated.');
		self::assertEquals(3, count($this->getTempFiles()), 'Wrong file count generated.');
	}

	public function testGeneratingAndFilters(): void
	{
		$this->object->addFileFilter(function ($code) {
			return strrev($code);
		});
		$this->object->addFileFilter(function ($code, Compiler $compiler, $file) {
			return pathinfo($file, PATHINFO_FILENAME) . ':' . $code . ',';
		});
		$this->object->addFilter(function ($code, Compiler $compiler) {
			return '-' . $code;
		});
		$this->object->addFilter(function ($code) {
			return $code . $code;
		});

		$expectedContent = '-' . PHP_EOL . 'a:cba,' . PHP_EOL . 'b:fed,' . PHP_EOL .
			'c:ihg,-' . PHP_EOL . 'a:cba,' . PHP_EOL . 'b:fed,' . PHP_EOL . 'c:ihg,';

		$files = $this->object->generate();

		self::assertTrue(is_numeric($files[0]->lastModified), 'Generate does not provide last modified timestamp correctly.');

		$content = file_get_contents($this->object->getOutputDir() . '/' . $files[0]->file);

		self::assertEquals($expectedContent, $content);
	}

	public function testGenerateReturnsSourceFilePaths(): void
	{
		$res = $this->object->generate();
		self::assertIsArray($res[0]->sourceFiles);
		self::assertCount(3, $res[0]->sourceFiles);
		self::assertFileExists($res[0]->sourceFiles[0]);
	}

	public function testFilters(): void
	{
		$filter = function ($code, \WebLoader\Compiler $loader) {
			return $code . $code;
		};
		$this->object->addFilter($filter);
		$this->object->addFilter($filter);
		self::assertEquals(array($filter, $filter), $this->object->getFilters());
	}

	public function testFileFilters(): void
	{
		$filter = function ($code, \WebLoader\Compiler $loader, $file = null) {
			return $code . $code;
		};
		$this->object->addFileFilter($filter);
		$this->object->addFileFilter($filter);
		self::assertEquals(array($filter, $filter), $this->object->getFileFilters());
	}

	public function testNonCallableFilter(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->object->addFilter(4);
	}

	public function testNonCallableFileFilter(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->object->addFileFilter(4);
	}

}
