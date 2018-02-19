<?php

namespace App\Model;

use Nette\Application\Routers\Route;

final class PathRoute extends Route
{
	public function __construct($mask, callable $callback)
	{
		parent::__construct($mask, [
			self::PRESENTER_KEY => 'Tim:Micro',
			'callback' => $callback,
		]);
	}


}