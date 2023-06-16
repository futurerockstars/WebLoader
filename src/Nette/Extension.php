<?php

namespace WebLoader\Nette;

use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\Schema\Expect;
use Nette\Schema\Helpers;
use Nette\Utils\Finder;
use SplFileInfo;
use WebLoader\Compiler;
use WebLoader\DefaultOutputNamingConvention;
use WebLoader\FileCollection;
use WebLoader\FileNotFoundException;
use WebLoader\Nette\Diagnostics\Panel;
use function file_exists;
use function is_array;
use function is_dir;
use function is_writable;
use function natsort;
use function rtrim;
use function sprintf;
use function strtolower;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * @property-read array<mixed> $config
 */
class Extension extends CompilerExtension
{

	public const DEFAULT_TEMP_PATH = 'webtemp';

	private string $wwwDir;

	private bool $debugMode;

	public function __construct(string $wwwDir, bool $debugMode)
	{
		$this->wwwDir = $wwwDir;
		$this->debugMode = $debugMode;
	}

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'jsDefaults' => Expect::structure([
				'checkLastModified' => Expect::bool(true),
				'debug' => Expect::bool(false),
				'sourceDir' => Expect::string($this->wwwDir . '/js'),
				'tempDir' => Expect::string($this->wwwDir . '/' . self::DEFAULT_TEMP_PATH),
				'tempPath' => Expect::string(self::DEFAULT_TEMP_PATH),
				'files' => Expect::array(),
				'watchFiles' => Expect::array(),
				'remoteFiles' => Expect::array(),
				'filters' => Expect::array(),
				'fileFilters' => Expect::array(),
				'joinFiles' => Expect::bool(true),
				'namingConvention' => Expect::string('@' . $this->prefix('jsNamingConvention')),
			])->castTo('array'),
			'cssDefaults' => Expect::structure([
				'checkLastModified' => Expect::bool(true),
				'debug' => Expect::bool(false),
				'sourceDir' => Expect::string($this->wwwDir . '/css')->dynamic(),
				'tempDir' => Expect::string($this->wwwDir . '/' . self::DEFAULT_TEMP_PATH),
				'tempPath' => Expect::string(self::DEFAULT_TEMP_PATH),
				'files' => Expect::array(),
				'watchFiles' => Expect::array(),
				'remoteFiles' => Expect::array(),
				'filters' => Expect::array(),
				'fileFilters' => Expect::array(),
				'joinFiles' => Expect::bool(true),
				'namingConvention' => Expect::string('@' . $this->prefix('cssNamingConvention')),
			])->castTo('array'),
			'js' => Expect::array(),
			'css' => Expect::array(),
			'debugger' => Expect::bool($this->debugMode),
		])->castTo('array');
	}

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$builder->addDefinition($this->prefix('cssNamingConvention'))
			->setFactory([DefaultOutputNamingConvention::class, 'createCssConvention']);

		$builder->addDefinition($this->prefix('jsNamingConvention'))
			->setFactory([DefaultOutputNamingConvention::class, 'createJsConvention']);

		if ($config['debugger']) {
			$builder->addDefinition($this->prefix('tracyPanel'))
				->setFactory(Panel::class);
		}

		$builder->parameters['webloader'] = $config;

		$loaderFactoryTempPaths = [];

		foreach (['css', 'js'] as $type) {
			foreach ($config[$type] as $name => $wlConfig) {
				$wlConfig = Helpers::merge($wlConfig, $config[$type . 'Defaults']);
				$this->addWebLoader($builder, $type . ucfirst($name), $wlConfig);
				$loaderFactoryTempPaths[strtolower($name)] = $wlConfig['tempPath'];

				if (!is_dir($wlConfig['tempDir']) || !is_writable($wlConfig['tempDir'])) {
					throw new CompilationException(
						sprintf("You must create a writable directory '%s'", $wlConfig['tempDir']),
					);
				}
			}
		}

		$builder->addDefinition($this->prefix('factory'))
			->setFactory(LoaderFactory::class, [$loaderFactoryTempPaths, $this->name]);
	}

	private function addWebLoader(ContainerBuilder $builder, $name, $config)
	{
		$filesServiceName = $this->prefix($name . 'Files');

		$files = $builder->addDefinition($filesServiceName)
			->setFactory(FileCollection::class)
			->setArguments([$config['sourceDir']]);

		foreach ($this->findFiles($config['files'], $config['sourceDir']) as $file) {
			$files->addSetup('addFile', [$file]);
		}

		foreach ($this->findFiles($config['watchFiles'], $config['sourceDir']) as $file) {
			$files->addSetup('addWatchFile', [$file]);
		}

		$files->addSetup('addRemoteFiles', [$config['remoteFiles']]);

		$compiler = $builder->addDefinition($this->prefix($name . 'Compiler'))
			->setFactory(Compiler::class)
			->setArguments([
				'@' . $filesServiceName,
				$config['namingConvention'],
				$config['tempDir'],
			]);

		$compiler->addSetup('setJoinFiles', [$config['joinFiles']]);

		if ($builder->parameters['webloader']['debugger']) {
			$compiler->addSetup('@' . $this->prefix('tracyPanel') . '::addLoader', [
				$name,
				'@' . $this->prefix($name . 'Compiler'),
			]);
		}

		foreach ($config['filters'] as $filter) {
			$compiler->addSetup('addFilter', [$filter]);
		}

		foreach ($config['fileFilters'] as $filter) {
			$compiler->addSetup('addFileFilter', [$filter]);
		}

		if (isset($config['debug']) && $config['debug']) {
			$compiler->addSetup('enableDebugging');
		}

		$compiler->addSetup('setCheckLastModified', [$config['checkLastModified']]);

		// todo css media
	}

	/**
	 * @param array $filesConfig
	 * @param string $sourceDir
	 * @return array
	 */
	private function findFiles(array $filesConfig, $sourceDir)
	{
		$normalizedFiles = [];

		foreach ($filesConfig as $file) {
			// finder support
			if (is_array($file) && isset($file['files']) && (isset($file['in']) || isset($file['from']))) {
				$finder = Finder::findFiles($file['files']);

				if (isset($file['exclude'])) {
					$finder->exclude($file['exclude']);
				}

				if (isset($file['in'])) {
					$finder->in(is_dir($file['in']) ? $file['in'] : $sourceDir . DIRECTORY_SEPARATOR . $file['in']);
				} else {
					$finder->from(
						is_dir($file['from']) ? $file['from'] : $sourceDir . DIRECTORY_SEPARATOR . $file['from'],
					);
				}

				$foundFilesList = [];
				foreach ($finder as $foundFile) {
					/** @var SplFileInfo $foundFile */
					$foundFilesList[] = $foundFile->getPathname();
				}

				natsort($foundFilesList);

				foreach ($foundFilesList as $foundFilePathname) {
					$normalizedFiles[] = $foundFilePathname;
				}
			} else {
				$this->checkFileExists($file, $sourceDir);
				$normalizedFiles[] = $file;
			}
		}

		return $normalizedFiles;
	}

	/**
	 * @param string $file
	 * @param string $sourceDir
	 * @throws FileNotFoundException
	 */
	protected function checkFileExists($file, $sourceDir)
	{
		if (!file_exists($file)) {
			$tmp = rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR . $file;
			if (!file_exists($tmp)) {
				throw new FileNotFoundException(sprintf("Neither '%s' or '%s' was found", $file, $tmp));
			}
		}
	}

}
