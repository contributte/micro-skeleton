<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory as NetteTemplateFactory;

final class TemplateFactory extends NetteTemplateFactory
{

	public function createTemplate(Control $control = null, string $class = null): Template
	{
		$template = parent::createTemplate($control);

		$template->add('_app', (object) [
			'layout' => __DIR__ . '/../templates/@layout.latte',
		]);

		return $template;
	}

}
