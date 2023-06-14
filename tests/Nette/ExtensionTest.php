<?php

namespace WebLoader\Test\Nette;

use Nette\Utils\Finder;

class ExtensionTest extends \PHPUnit\Framework\TestCase
{

	/** @var \Nette\DI\Container */
	private $container;

	private function prepareContainer($configFiles)
	{
		$tempDir = __DIR__ . '/../temp';
		foreach (Finder::findFiles('*')->exclude('.gitignore')->from($tempDir . '/cache') as $file) {
			unlink((string) $file);
		}

		$configurator = new \Nette\Configurator();
		$configurator->setTempDirectory($tempDir);

		foreach ($configFiles as $file) {
			$configurator->addConfig($file, FALSE);
		}

		$configurator->addParameters(array(
			'wwwDir' =>  __DIR__ . '/..',
			'fixturesDir' =>  __DIR__ . '/../fixtures',
			'tempDir' => $tempDir,
		));

		$extension = new \WebLoader\Nette\Extension();
		$extension->install($configurator);

		$this->container = @$configurator->createContainer(); // sends header X-Powered-By, ...
	}

	public function testJsCompilerService(): void
	{
		$this->prepareContainer(array(__DIR__ . '/../fixtures/extension.neon'));
		self::assertInstanceOf('WebLoader\Compiler', $this->container->getService('webloader.jsDefaultCompiler'));
	}

	public function testExcludeFiles(): void
	{
		$this->prepareContainer(array(__DIR__ . '/../fixtures/extension.neon'));
		$files = $this->container->getService('webloader.jsExcludeCompiler')->getFileCollection()->getFiles();

		self::assertTrue(in_array(realpath(__DIR__ . '/../fixtures/a.txt'), $files));
		self::assertFalse(in_array(realpath(__DIR__ . '/../fixtures/dir/one.js'), $files));
	}

	public function testJoinFilesOn(): void
	{
		$this->prepareContainer(array(
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionJoinFilesTrue.neon',
		));
		self::assertTrue($this->container->getService('webloader.jsDefaultCompiler')->getJoinFiles());
	}

	public function testJoinFilesOff(): void
	{
		$this->prepareContainer(array(
			__DIR__ . '/../fixtures/extension.neon',
			__DIR__ . '/../fixtures/extensionJoinFilesFalse.neon',
		));
		self::assertFalse($this->container->getService('webloader.jsDefaultCompiler')->getJoinFiles());
	}

	public function testJoinFilesOffInOneService(): void
	{
		$this->prepareContainer(array(
			__DIR__ . '/../fixtures/extension.neon',
		));
		self::assertFalse($this->container->getService('webloader.cssJoinOffCompiler')->getJoinFiles());
	}

	public function testExtensionName(): void
	{
		$tempDir = __DIR__ . '/../temp';
		$class = 'ExtensionNameServiceContainer';

		$configurator = new \Nette\Configurator();
		$configurator->setTempDirectory($tempDir);
		$configurator->addParameters(array('container' => array('class' => $class)));
		$configurator->onCompile[] = function ($configurator, \Nette\DI\Compiler $compiler) {
			$compiler->addExtension('Foo', new \WebLoader\Nette\Extension());
		};
		$configurator->addConfig(__DIR__ . '/../fixtures/extensionName.neon', false);
		$container = $configurator->createContainer();

		self::assertInstanceOf('WebLoader\Compiler', $container->getService('Foo.cssDefaultCompiler'));
		self::assertInstanceOf('WebLoader\Compiler', $container->getService('Foo.jsDefaultCompiler'));
	}

}
