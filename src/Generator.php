<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ApiDocu;

use Nette;
use Nette\Application\Request;
use Nette\Application\IRouter;
use Ublaboo\ApiRouter\ApiRoute;
use Tracy\Debugger;

class Generator extends Nette\Object
{

	/**
	 * @var Nette\Application\UI\ITemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var Nette\Http\Request
	 */
	private $httpRequest;

	/**
	 * @var string
	 */
	private $api_dir;


	public function __construct(
		$api_dir,
		Nette\Application\UI\ITemplateFactory $templateFactory,
		Nette\Http\Request $httpRequest
	) {
		$this->api_dir = $api_dir;
		$this->templateFactory = $templateFactory;
		$this->httpRequest = $httpRequest;
	}


	public function generateAll(IRouter $router)
	{
		if ($router instanceof ApiRoute) {
			$routes = [$router];
		} else if ($router instanceof \IteratorAggregate) {
			$routes = $this->getApiRoutesFromIterator($router);
		}

		$i = 1;

		foreach ($routes as $route) {
			$template = $this->createTemplate('api_docu_one.latte');

			@mkdir($this->api_dir);

			file_put_contents(
				"{$this->api_dir}/{$i}.html",
				(string) $this->generateOne($route, $routes, $template)
			);

			$i++;
		}

		$this->generateSuccess();
	}


	public function generateTarget(ApiRoute $route, Request $request)
	{
		$template = $this->createTemplate('api_docu_matched.latte');

		$template->setParameters([
			'route'       => $route,
			'request'     => $request,
			'httpRequest' => $this->httpRequest
		]);

		if (class_exists('Tracy\Debugger')) {
			Debugger::$productionMode = TRUE;
		}

		echo (string) $template;
	}


	public function generateOne(ApiRoute $route, $routes, $template)
	{
		$template->setParameters([
			'route'       => $route,
			'routes'      => $routes
		]);

		return $template;
	}


	public function generateSuccess()
	{
		$template = $this->createTemplate('api_docu_success.latte');

		$template->setParameters([
			'apiDir' => $this->api_dir
		]);

		if (class_exists('Tracy\Debugger')) {
			Debugger::$productionMode = TRUE;
		}

		echo (string) $template;
	}


	public function createTemplate($which)
	{
		$template = $this->templateFactory->createTemplate();
		$template->setFile(__DIR__ . '/templates/' . $which);

		$template->base_dir = __DIR__ . '/templates';

		$template->addFilter('routeMaskStyles', function($mask) {
			return str_replace(['<', '>'], ['<span class="apiDocu-mask-param">&lt;', '&gt;</span>'], $mask);
		});

		return $template;
	}


	/********************************************************************************
	 *                                   INTERNAL                                   *
	 ********************************************************************************/


	/**
	 * Recursively flatten \IteratorAggregate (probably Nette\Application\Routers\RouteList)
	 * @param  \IteratorAggregate $i
	 * @return array
	 */
	private function getApiRoutesFromIterator(\IteratorAggregate $i)
	{
		$return = [];

		foreach ($i as $router) {
			if ($router instanceof ApiRoute) {
				$return[] = $router;
			} else if ($router instanceof \IteratorAggregate) {
				$routes = $this->getApiRoutesFromIterator($router);

				foreach ($routes as $route) {
					$return[] = $route;
				}
			}
		}

		return $return;
	}

}
