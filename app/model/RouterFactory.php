<?php

namespace App\Model;

use Nette\Application\Routers\RouteList;
use Nette\DI\Container;

class RouterFactory
{

	/** @var Container */
	private $context;

	/**
	 * @param Container $context
	 */
	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	/**
	 * @return RouteList
	 */
	public static function createRouter()
	{
		$router = new RouteList();

		$router[] = new PathRoute('<path=default>', function (MicroPresenter $presenter) {
			$path = $presenter->getRequest()->getParameter('path');
			$template = $presenter->createTemplate();

			if (file_exists(__DIR__ . '/../templates/' . $path . '.latte')) {
				$template->setFile(__DIR__ . '/../templates/' . $path . '.latte');
			} else {
				$template->setFile(__DIR__ . '/../templates/errors/404.latte');
			}

			return $template;
		});

		return $router;
	}

}