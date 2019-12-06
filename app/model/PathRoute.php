<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Application\Routers\Route;

final class PathRoute extends Route
{
	public function __construct($mask, callable $callback)
	{
		parent::__construct($mask, [
			'presenter' => 'Tim:Micro',
			'callback' => $callback,
		]);
	}


}
