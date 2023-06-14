<?php

namespace WebLoader\Test\Path;

use PHPUnit\Framework\TestCase;
use WebLoader\Path;

class PathTest extends TestCase
{

	public function testNormalize(): void
	{
		$normalized = Path::normalize('/path/to//project//that/contains/0/in/it');
		self::assertEquals('/path/to/project/that/contains/0/in/it', $normalized);
	}

}
