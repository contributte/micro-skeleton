<?php

namespace App\Model;

use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory as NetteTemplateFactory;

final class TemplateFactory extends NetteTemplateFactory
{

	/**
	 * @param Control|NULL $control
	 * @return Template
	 */
	public function createTemplate(Control $control = NULL)
	{
		$template = parent::createTemplate($control);

		$template->add('_app', (object) [
			'layout' => __DIR__ . '/../templates/@layout.latte',
		]);

		return $template;
	}

}