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


	/**
	 * @param string                                $api_dir
	 * @param Nette\Application\UI\ITemplateFactory $templateFactory
	 * @param Nette\Http\Request                    $httpRequest
	 */
	public function __construct(
		$api_dir,
		Nette\Application\UI\ITemplateFactory $templateFactory,
		Nette\Http\Request $httpRequest
	) {
		$this->api_dir = $api_dir;
		$this->templateFactory = $templateFactory;
		$this->httpRequest = $httpRequest;
	}


	/**
	 * @param  IRouter $router
	 * @return void
	 */
	public function generateAll(IRouter $router)
	{
		$routes = [];
		$sections = [];
		$file_name = 1;

		if ($router instanceof ApiRoute) {
			$routes = [$router];
		} else if ($router instanceof \IteratorAggregate) {
			$routes = $this->getApiRoutesFromIterator($router);
		}

		foreach ($routes as $route) {
			if ($route->getSection()) {
				if (empty($sections[$route->getSection()])) {
					$sections[$route->getSection()] = [];
				}

				$sections[$route->getSection()][$file_name++] = $route;
			} else {
				$sections[$file_name++] = $route;
			}
		}

		mkdir($this->api_dir);

		/**
		 * Create index.html
		 */
		$this->generateIndex($sections);

		/**
		 * Create *.html for each defined ApiRoute
		 */
		foreach ($sections as $section_name => $routes) {
			if (is_array($routes)) {
				foreach ($routes as $file_name => $route) {
					$this->generateOne($route, $sections, "$section_name.$file_name");
				}
			} else {
				$this->generateOne($routes, $sections, $section_name);
			}
		}

		$this->generateSuccess();
	}


	/**
	 * @param  ApiRoute $route
	 * @param  Request  $request
	 * @return void
	 */
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


	/**
	 * @param  ApiRoute $route
	 * @param  array    $sections
	 * @param  string   $file_name
	 * @return void
	 */
	public function generateOne(ApiRoute $route, $sections, $file_name)
	{
		$template = $this->createTemplate('api_docu_one.latte');

		$template->setParameters([
			'route'       => $route,
			'sections'    => $sections
		]);

		file_put_contents("{$this->api_dir}/{$file_name}.html", (string) $template);
	}


	/**
	 * @param  array $sections
	 * @return void
	 */
	public function generateIndex($sections)
	{
		$template = $this->createTemplate('api_docu_index.latte');

		$template->setParameters([
			'sections' => $sections
		]);

		file_put_contents("{$this->api_dir}/index.html", (string) $template);
	}


	/**
	 * @return void
	 */
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


	/**
	 * @param  string
	 * @return Nette\Application\UI\ITemplate
	 */
	public function createTemplate($which)
	{
		$template = $this->templateFactory->createTemplate();
		$template->setFile(__DIR__ . '/templates/' . $which);

		if ($template instanceof Nette\Application\UI\ITemplate) {
			$template->base_dir = __DIR__ . '/templates';
		}

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
