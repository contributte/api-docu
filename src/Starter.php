<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ApiDocu;

use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Application\Routers\RouteList;
use Ublaboo\ApiRouter\ApiRoute;

class Starter
{

	public const API_DOCU_STARTER_QUERY_KEY_TARGET = '__apiDocu';
	public const API_DOCU_STARTER_QUERY_KEY_GENERATE = '__apiDocuGenerate';

	/**
	 * @var callable[]
	 */
	public $onMatch;

	/**
	 * @var Generator
	 */
	private $generator;

	/**
	 * @var IRouter
	 */
	private $router;


	public function __construct(
		Generator $generator,
		IRouter $router
	) {
		$this->generator = $generator;
		$this->router = $router;

		if ($router instanceof RouteList) {
			$this->attachEvents($router);
		}
	}


	/**
	 * Event thatis firex when particular ApiRoute is matched
	 */
	public function routeMatched(ApiRoute $route, Request $request): void
	{
		$format = $request->getParameter(self::API_DOCU_STARTER_QUERY_KEY_GENERATE);

		if ($format !== null) {
			$this->generator->generateAll($this->router);

			exit(0);
		}

		$format = $request->getParameter(self::API_DOCU_STARTER_QUERY_KEY_TARGET);

		if ($format !== null) {
			$this->generator->generateTarget($route, $request);

			exit(0);
		}
	}


	/**
	 * Find ApiRoutes and add listener to each ApiRoute::onMatch event
	 */
	protected function attachEvents(RouteList $routeList): void
	{
		foreach ($routeList as $route) {
			if ($route instanceof ApiRoute) {
				$route->onMatch[] = [$this, 'routeMatched'];
			}
		}
	}
}
