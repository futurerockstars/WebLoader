<?php declare(strict_types = 1);

namespace WebLoader\Filter;

use tubalmartin\CssMin\Minifier;
use WebLoader\Compiler;

final class CssMinFilter
{

	public function __invoke(string $code, Compiler $compiler, string $file = ''): string
	{
		return (new Minifier())->run($code);
	}

}
