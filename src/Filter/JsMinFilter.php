<?php declare(strict_types = 1);

namespace WebLoader\Filter;

use JShrink\Minifier;
use WebLoader\Compiler;

class JsMinFilter
{

	public function __invoke(string $code, Compiler $compiler, string $file = ''): ?string
	{
		$result = Minifier::minify($code);

		if (!$result) {
			return null;
		}

		return (string) $result;
	}

}
