<?php

namespace WebLoader\Nette;

use Nette\Utils\Html;

/**
 * JavaScript loader
 */
class JavaScriptLoader extends WebLoader
{

	/**
	 * Get script element
	 *
	 * @param string $source
	 * @return Html
	 */
	public function getElement($source)
	{
		return Html::el('script')->src($source);
	}

}
