<?php

namespace WebLoader\Test\Path;

use WebLoader\Path;

class PathTest extends \PHPUnit\Framework\TestCase
{

	public function testNormalize(): void
	{
		$normalized = Path::normalize('/path/to//project//that/contains/0/in/it');
		self::assertEquals('/path/to/project/that/contains/0/in/it', $normalized);
	}

}
