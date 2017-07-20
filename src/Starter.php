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
use Nette\Http;
use Ublaboo\ApiRouter\ApiRoute;

class Starter
{
	public const API_DOCU_STARTER_QUERY_KEY_TARGET = '__apiDocu';
	public const API_DOCU_STARTER_QUERY_KEY_GENERATE = '__apiDocuGenerate';

	/**
	 * @var Generator
	 */
	private $generator;

	/**
	 * @var IRouter
	 */
	private $router;

	/**
	 * @var Http\Response
	 */
	private $response;

	/**
	 * @var Http\Request
	 */
	private $httpRequest;


	/**
	 * @param Generator     $generator
	 * @param IRouter       $router
	 * @param Http\Response $response
	 * @param Http\Request  $httpRequest
	 */
	public function __construct(
		Generator $generator,
		IRouter $router,
		Http\Response $response,
		Http\Request $httpRequest
	) {
		$this->generator = $generator;
		$this->router = $router;

		$this->response = $response;
		$this->httpRequest = $httpRequest;

		if ($router instanceof RouteList) {
			$this->attachEvents();
		}
	}


	/**
	 * Event thatis firex when particular ApiRoute is matched
	 */
	public function routeMatched(ApiRoute $route, Request $request): void
	{
		if (($format = $request->getParameter(self::API_DOCU_STARTER_QUERY_KEY_GENERATE)) !== null) {
			$this->generator->generateAll($this->router);

			exit(0);
		}

		if (($format = $request->getParameter(self::API_DOCU_STARTER_QUERY_KEY_TARGET)) !== null) {
			$this->generator->generateTarget($route, $request);

			exit(0);
		}
	}


	/**
	 * Find ApiRoutes and add listener to each ApiRoute::onMatch event
	 */
	protected function attachEvents(): void
	{
		foreach ($this->router as $route) {
			if ($route instanceof ApiRoute) {
				$route->onMatch[] = [$this, 'routeMatched'];
			}
		}
	}
}
