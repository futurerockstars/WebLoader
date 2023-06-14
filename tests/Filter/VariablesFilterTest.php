<?php

namespace WebLoader\Test\Filter;

use WebLoader\Filter\VariablesFilter;

class VariablesFilterTest extends \PHPUnit\Framework\TestCase
{

	/** @var VariablesFilter */
	private $object;

	protected function setUp(): void
	{
		$this->object = new VariablesFilter(array(
			'foo' => 'bar',
		));
	}

	public function testReplace(): void
	{
		$this->object->bar = 'baz';

		$filter = $this->object;

		$code = 'a tak sel {{$foo}} za {{$bar}}em a potkali druheho {{$foo}}';

		$filtered = $filter($code);

		self::assertEquals('a tak sel bar za bazem a potkali druheho bar', $filtered);
	}

	public function testDelimiters(): void
	{
		$this->object->setDelimiter('[', ']');
		self::assertEquals('bar', call_user_func($this->object, '[foo]'));
	}

}
