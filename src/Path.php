<?php

namespace WebLoader;

use function array_pop;
use function array_push;
use function explode;
use function implode;
use function strpos;
use function strtr;
use function trim;

class Path
{

	public static function normalize($path)
	{
		$path = strtr($path, '\\', '/');
		$root = strpos($path, '/') === 0 ? '/' : '';
		$pieces = explode('/', trim($path, '/'));
		$res = [];

		foreach ($pieces as $piece) {
			if ($piece === '.' || $piece === '') {
				continue;
			}

			if ($piece === '..') {
				array_pop($res);
			} else {
				array_push($res, $piece);
			}
		}

		return $root . implode('/', $res);
	}

}
