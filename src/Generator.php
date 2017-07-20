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
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Http;
use Tracy\Debugger;
use Ublaboo\ApiRouter\ApiRoute;

class Generator
{

	/**
	 * @var ITemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * @var string
	 */
	private $appDir;

	/**
	 * @var array
	 */
	private $httpAuth;


	public function __construct(
		string $appDir,
		array $httpAuth,
		ITemplateFactory $templateFactory,
		Http\Request $httpRequest
	) {
		$this->appDir = $appDir;
		$this->httpAuth = $httpAuth;
		$this->templateFactory = $templateFactory;
		$this->httpRequest = $httpRequest;
	}


	public function generateAll(IRouter $router): void
	{
		$sections = $this->splitRoutesIntoSections($router);

		if (!file_exists($this->appDir) && !is_dir($this->appDir)) {
			mkdir($this->appDir);
		}

		/**
		 * Create index.php
		 */
		$this->generateIndex($sections);

		/**
		 * Create *.php for each defined ApiRoute
		 */
		foreach ($sections as $sectionName => $routes) {
			if (is_array($routes)) {
				foreach ($routes as $fileName => $route) {
					$this->generateOne($route, $sections, "$sectionName.$fileName");
				}
			} else {
				$this->generateOne($routes, $sections, $sectionName);
			}
		}

		$this->generateSuccess();
	}


	public function generateTarget(ApiRoute $route, Request $request): void
	{
		$template = $this->createTemplate('api_docu_matched.latte');

		$template->setParameters([
			'route' => $route,
			'request' => $request,
			'httpRequest' => $this->httpRequest,
		]);

		if (class_exists('Tracy\Debugger')) {
			Debugger::$productionMode = true;
		}

		echo (string) $template;
	}


	public function generateOne(ApiRoute $route, array $sections, string $fileName): void
	{
		$template = $this->createTemplate('api_docu_one.latte');

		$template->setParameters([
			'route' => $route,
			'sections' => $sections,
		]);

		file_put_contents(
			"{$this->appDir}/{$fileName}.php",
			$this->getHttpAuthSnippet() . $template
		);
	}


	public function generateIndex(array $sections): void
	{
		$template = $this->createTemplate('api_docu_index.latte');

		$template->setParameters([
			'sections' => $sections,
		]);

		file_put_contents(
			"{$this->appDir}/index.php",
			$this->getHttpAuthSnippet() . $template
		);
	}


	public function generateSuccess(): void
	{
		$template = $this->createTemplate('api_docu_success.latte');

		$template->setParameters([
			'apiDir' => $this->appDir,
		]);

		if (class_exists('Tracy\Debugger')) {
			Debugger::$productionMode = true;
		}

		echo (string) $template;
	}


	public function createTemplate(string $which): ITemplate
	{
		$template = $this->templateFactory->createTemplate();

		$template->addFilter(null, 'Ublaboo\ApiDocu\TemplateFilters::common');

		$template->setFile(__DIR__ . '/templates/' . $which);

		if ($template instanceof ITemplate) {
			$template->base_dir = __DIR__ . '/templates';
		}

		$template->addFilter('routeMaskStyles', function ($mask) {
			return str_replace(['<', '>'], ['<span class="apiDocu-mask-param">&lt;', '&gt;</span>'], $mask);
		});

		$template->addFilter('apiDocuResponseCode', function ($code) {
			if ($code >= 200 && $code <= 202) {
				return "<span class=\"apiDocu-code-success\">{$code}</span>";
			} elseif ($code >= 300 && $code < 500) {
				return "<span class=\"apiDocu-code-warning\">{$code}</span>";
			} elseif ($code >= 500) {
				return "<span class=\"apiDocu-code-error\">{$code}</span>";
			}

			return "<span class=\"apiDocu-code-other\">{$code}</span>";
		});

		return $template;
	}


	/********************************************************************************
	 *                                   INTERNAL                                   *
	 ********************************************************************************/


	private function splitRoutesIntoSections(IRouter $router): array
	{
		$routes = [];
		$sections = [];
		$fileName = 1;

		if ($router instanceof ApiRoute) {
			$routes = [$router];
		} elseif ($router instanceof \IteratorAggregate) {
			$routes = $this->getApiRoutesFromIterator($router);
		}

		foreach ($routes as $route) {
			if ($route->getSection()) {
				if (empty($sections[$route->getSection()])) {
					$sections[$route->getSection()] = [];
				}

				$sections[$route->getSection()][$fileName++] = $route;
			} else {
				$sections[$fileName++] = $route;
			}
		}

		return $sections;
	}


	/**
	 * Recursively flatten \IteratorAggregate (probably Nette\Application\Routers\RouteList)
	 */
	private function getApiRoutesFromIterator(\IteratorAggregate $i): array
	{
		$return = [];

		foreach ($i as $router) {
			if ($router instanceof ApiRoute) {
				$return[] = $router;
			} elseif ($router instanceof \IteratorAggregate) {
				$routes = $this->getApiRoutesFromIterator($router);

				foreach ($routes as $route) {
					$return[] = $route;
				}
			}
		}

		return $return;
	}


	private function getHttpAuthSnippet(): string
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
			. '} ?>';
	}
}
