<?php

namespace WebLoader\Filter;

use RuntimeException;
use RuntimeExeption;
use function fclose;
use function fwrite;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use const PHP_EOL;

/**
 * Simple process wrapper
 */
class Process
{

	/**
	 * @param string $cmd
	 * @param string|NULL $stdin
	 * @param string|NULL $cwd
	 * @param array|NULL $env
	 * @return string
	 * @throws RuntimeExeption
	 */
	public static function run($cmd, $stdin = null, $cwd = null, ?array $env = null)
	{
		$descriptorspec = [
			0 => ['pipe', 'r'], // stdin
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr
		];

		$pipes = [];
		$proc = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);

		if (!empty($stdin)) {
			fwrite($pipes[0], $stdin . PHP_EOL);
		}

		fclose($pipes[0]);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);

		$code = proc_close($proc);

		if ($code != 0) {
			throw new RuntimeException($stderr, $code);
		}

		return $stdout;
	}

}
