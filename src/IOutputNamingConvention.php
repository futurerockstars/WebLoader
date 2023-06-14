<?php

namespace WebLoader;

/**
 * IOutputNamingConvention
 */
interface IOutputNamingConvention
{

	public function getFilename(array $files, Compiler $compiler);

}
