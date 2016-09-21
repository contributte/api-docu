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
	 * @var array
	 */
	private $httpAuth;


	/**
	 * @param string                                $api_dir
	 * @param array                                 $httpAuth
	 * @param Nette\Application\UI\ITemplateFactory $templateFactory
	 * @param Nette\Http\Request                    $httpRequest
	 */
	public function __construct(
		$api_dir,
		array $httpAuth,
		Nette\Application\UI\ITemplateFactory $templateFactory,
		Nette\Http\Request $httpRequest
	) {
		$this->api_dir = $api_dir;
		$this->httpAuth = $httpAuth;
		$this->templateFactory = $templateFactory;
		$this->httpRequest = $httpRequest;
	}


	/**
	 * @param  IRouter $router
	 * @return void
	 */
	public function generateAll(IRouter $router)
	{
		$sections = $this->splitRoutesIntoSections($router);

		if (!file_exists($this->api_dir) && !is_dir($this->api_dir)) {
			mkdir($this->api_dir);
		}

		/**
		 * Create index.php
		 */
		$this->generateIndex($sections);

		/**
		 * Create *.php for each defined ApiRoute
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

		file_put_contents(
			"{$this->api_dir}/{$file_name}.php",
			$this->getHttpAuthSnippet() . $template
		);
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

		file_put_contents(
			"{$this->api_dir}/index.php",
			$this->getHttpAuthSnippet() . $template
		);
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

		$template->addFilter(NULL, 'Ublaboo\ApiDocu\TemplateFilters::common');

		$template->setFile(__DIR__ . '/templates/' . $which);

		if ($template instanceof Nette\Application\UI\ITemplate) {
			$template->base_dir = __DIR__ . '/templates';
		}

		$template->addFilter('routeMaskStyles', function($mask) {
			return str_replace(['<', '>'], ['<span class="apiDocu-mask-param">&lt;', '&gt;</span>'], $mask);
		});

		$template->addFilter('apiDocuResponseCode', function($code) {
			if ($code >= 200 && $code <= 202) {
				return "<span class=\"apiDocu-code-success\">{$code}</span>";
			} else if ($code >= 300 && $code < 500) {
				return "<span class=\"apiDocu-code-warning\">{$code}</span>";
			} else if ($code >= 500) {
				return "<span class=\"apiDocu-code-error\">{$code}</span>";
			}

			return "<span class=\"apiDocu-code-other\">{$code}</span>";
		});

		return $template;
	}


	/********************************************************************************
	 *                                   INTERNAL                                   *
	 ********************************************************************************/


	private function splitRoutesIntoSections(IRouter $router)
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

		return $sections;
	}


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


	/**
	 * @return string
	 */
	private function getHttpAuthSnippet()
	{
		if (!$this->httpAuth['user'] || !$this->httpAuth['password']) {
			return '';
		}

		$u = $this->httpAuth['user'];
		$p = $this->httpAuth['password'];

		return "<?php if (!isset(\$_SERVER['PHP_AUTH_USER']) || \$_SERVER['PHP_AUTH_USER'] !== '{$u}' || \$_SERVER['PHP_AUTH_PW'] !== '{$p}') {"
			. "	header('WWW-Authenticate: Basic realm=\"Api\"');"
			. "	header('HTTP/1.0 401 Unauthorized');"
			. "	die('Invalid authentication');"
			. "} ?>";
	}

}
