<?php

namespace WebLoader\Nette\Diagnostics;

use Latte;
use Latte\Runtime\Filters;
use Tracy\Debugger;
use Tracy\IBarPanel;
use WebLoader\Compiler;
use function basename;
use function filesize;
use function lcfirst;
use function pathinfo;
use function realpath;
use function str_replace;
use function strtolower;
use function substr;
use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

/**
 * Debugger panel.
 */
class Panel implements IBarPanel
{

	/** @var array */
	public static $types = [
		'css' => 'CSS files',
		'js' => 'JavaScript files',
		'less' => 'Less files',
		'scss' => 'Sass files',
	];

	/** @var array<Compiler> */
	private $compilers = [];

	/** @var array */
	private $size;

	/** @var array */
	private $files;

	/** @var array */
	private $sizes;

	public function __construct()
	{
		Debugger::getBar()->addPanel($this);
	}

	/**
	 * Registers a compiler.
	 *
	 * @param string $name
	 * @return Panel
	 */
	public function addLoader($name, Compiler $compiler)
	{
		$this->compilers[$name] = $compiler;

		return $this;
	}

	/**
	 * Computes the info.
	 *
	 * @return array
	 */
	private function compute()
	{
		if ($this->size !== null) {
			return $this->size;
		}

		$size = [
			'original' => 0,
			'combined' => 0,
		];
		$this->files = $this->sizes = [];

		foreach ($this->compilers as $name => $compiler) {
			$group = lcfirst(substr($name, $name[0] === 'c' ? 3 : 2));

			if (!isset($this->files[$group])) {
				$this->files[$group] = [];
			}

			if (!isset($this->sizes[$group])) {
				$this->sizes[$group] = ['.' => ['original' => 0, 'combined' => 0]];
			}

			$compilerCombinedSize = 0;
			foreach ($compiler->generate() as $generated) {
				$generatedSize = filesize($compiler->getOutputDir() . DIRECTORY_SEPARATOR . $generated->file);
				$size['combined'] += $generatedSize;
				$compilerCombinedSize += $generatedSize;

				foreach ($generated->sourceFiles as $file) {
					$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
					$file = str_replace('\\', DIRECTORY_SEPARATOR, realpath($file));

					if (!isset($this->files[$group][$extension])) {
						$this->files[$group][$extension] = [];
					}

					if (!isset($this->sizes[$group][$extension])) {
						$this->sizes[$group][$extension] = ['original' => 0];
					}

					$this->files[$group][$extension][] = [
						'name' => basename($file),
						'full' => $file,
						'size' => $fileSize = filesize($file),
					];

					$size['original'] += $fileSize;
					$this->sizes[$group][$extension]['original'] += $fileSize;
					$this->sizes[$group]['.']['original'] += $fileSize;
				}
			}

			$this->sizes[$group]['.']['combined'] += $compilerCombinedSize;
		}

		return $this->size = $size + ['ratio' => $size['original'] !== 0 ? $size['combined'] / $size['original'] * 100 : 0];
	}

	/**
	 * Renders loaded files table.
	 *
	 * @return string
	 */
	private function getTable()
	{
		$latte = new Latte\Engine();

		$latte->addFilter('extension', fn ($extension) => self::$types[$extension] ?? $extension);

		return $latte->renderToString(__DIR__ . '/panel.latte', [
			'files' => $this->files,
			'sizes' => $this->sizes,
			'size' => $this->size,
		]);
	}

	/**
	 * Returns panel content.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		return $this->compute() ? $this->getTable() : '';
	}

	/**
	 * Returns panel tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		$this->compute();

		return '<span title="WebLoader">'
			. '<svg viewBox="0 -50 600 600" style="vertical-align: bottom; width:1.23em; height:1.55em"><polygon fill="#1565C0" points="75.089,23.98 58.245,108.778 403.138,108.778 392.289,163.309 47.111,163.309 30.549,248.104 375.445,248.104 356.027,344.887 217.273,390.856 96.789,344.887 105.069,302.921 20.272,302.921 0,404.559 199.286,480.791 428.831,404.559 504.771,23.98"/></svg>'
			. Filters::bytes($this->size['combined'])
		. '</span>';
	}

}
