<?php

declare(strict_types=1);

namespace Contributte\ApiDocu;

use Contributte\ApiRouter\ApiRoute;
use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;

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
	 * @param array<mixed> $parameters
	 */
	public function routeMatched(ApiRoute $route, array $parameters): void
	{
		$format = $parameters[self::API_DOCU_STARTER_QUERY_KEY_GENERATE] ?? null;

		if ($format !== null) {
			$this->generator->generateAll($this->router);

			exit(0);
		}

		$format = $parameters[self::API_DOCU_STARTER_QUERY_KEY_TARGET] ?? null;

		if ($format !== null) {
			$this->generator->generateTarget($route, $parameters);

			exit(0);
		}
	}


	/**
	 * Find ApiRoutes and add listener to each ApiRoute::onMatch event
	 */
	protected function attachEvents(RouteList $routeList): void
	{
		foreach ($routeList->getRouters() as $route) {
			if ($route instanceof ApiRoute) {
				$route->onMatch[] = [$this, 'routeMatched'];
			}
		}
	}
}
