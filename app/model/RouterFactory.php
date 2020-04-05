<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Application\Routers\RouteList;

class RouterFactory
{

	public static function createRouter(): RouteList
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
