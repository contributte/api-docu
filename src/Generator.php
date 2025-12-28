<?php declare(strict_types = 1);

namespace Contributte\ApiDocu;

use Contributte\ApiRouter\ApiRoute;
use Nette\Application\Routers\RouteList;
use Nette\Application\UI\TemplateFactory;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\Request;
use Nette\Routing\Router;
use Tracy\Debugger;

class Generator
{

	private TemplateFactory $templateFactory;

	private Request $httpRequest;

	private string $appDir;

	/** @var array{user: ?string, password: ?string} */
	private array $httpAuth;

	/**
	 * @param array{user: ?string, password: ?string} $httpAuth
	 */
	public function __construct(
		string $appDir,
		array $httpAuth,
		TemplateFactory $templateFactory,
		Request $httpRequest
	)
	{
		$this->appDir = $appDir;
		$this->httpAuth = $httpAuth;
		$this->templateFactory = $templateFactory;
		$this->httpRequest = $httpRequest;
	}

	public function generateAll(Router $router): void
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
					$this->generateOne($route, $sections, $sectionName . '.' . $fileName);
				}
			} else {
				$this->generateOne($routes, $sections, (string) $sectionName);
			}
		}

		$this->generateSuccess();
	}

	/**
	 * @param array<mixed> $parameters
	 */
	public function generateTarget(ApiRoute $route, array $parameters): void
	{
		/** @var DefaultTemplate $template */
		$template = $this->createTemplate('api_docu_matched.latte');

		$template->setParameters([
			'route' => $route,
			'requestParameters' => $parameters,
			'httpRequest' => $this->httpRequest,
		]);

		if (class_exists(Debugger::class)) {
			Debugger::$productionMode = true;
		}

		echo (string) $template;
	}

	/**
	 * @param array<mixed> $sections
	 */
	public function generateOne(ApiRoute $route, array $sections, string $fileName): void
	{
		/** @var DefaultTemplate $template */
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

	/**
	 * @param array<mixed> $sections
	 */
	public function generateIndex(array $sections): void
	{
		/** @var DefaultTemplate $template */
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
		/** @var DefaultTemplate $template */
		$template = $this->createTemplate('api_docu_success.latte');

		$template->setParameters([
			'apiDir' => $this->appDir,
		]);

		if (class_exists(Debugger::class)) {
			Debugger::$productionMode = true;
		}

		echo (string) $template;
	}

	public function createTemplate(string $which): Template
	{
		$template = $this->templateFactory->createTemplate();
		assert($template instanceof DefaultTemplate);

		$template->getLatte()->addFilter('description', TemplateFilters::description(...));

		$template->setFile(__DIR__ . '/templates/' . $which);

		$template->setParameters(['base_dir' => __DIR__ . '/templates']);

		$template->addFilter('routeMaskStyles', fn ($mask): string => str_replace(['<', '>'], ['<span class="apiDocu-mask-param">&lt;', '&gt;</span>'], $mask));

		$template->addFilter('apiDocuResponseCode', function ($code): string {
			if ($code >= 200 && $code <= 202) {
				return "<span class=\"apiDocu-code-success\">{$code}</span>";
			}

			if ($code >= 300 && $code < 500) {
				return "<span class=\"apiDocu-code-warning\">{$code}</span>";
			}

			if ($code >= 500) {
				return "<span class=\"apiDocu-code-error\">{$code}</span>";
			}

			return "<span class=\"apiDocu-code-other\">{$code}</span>";
		});

		return $template;
	}


	/********************************************************************************
	 *                                   INTERNAL *
	 ********************************************************************************/

	/**
	 * @return array<int<1,max>|string, array<int,ApiRoute>|ApiRoute>
	 */
	private function splitRoutesIntoSections(Router $router): array
	{
		$routes = [];
		$sections = [];
		$fileName = 1;

		if ($router instanceof ApiRoute) {
			$routes = [$router];
		} elseif ($router instanceof RouteList) {
			$routes = $this->getApiRoutesFromIterator($router);
		}

		foreach ($routes as $route) {
			if ($route->getSection() !== null) {
				$sections[$route->getSection()] ??= [];
				$sections[$route->getSection()][$fileName] = $route; // @phpstan-ignore-line
			} else {
				$sections[$fileName] = $route;
			}

			$fileName++;
		}

		return $sections;
	}

	/**
	 * Recursively flatten \IteratorAggregate (probably Nette\Application\Routers\RouteList)
	 *
	 * @return array<ApiRoute>
	 */
	private function getApiRoutesFromIterator(RouteList $routeList): array
	{
		$return = [];
		$routers = $routeList->getRouters();

		foreach ($routers as $router) {
			if ($router instanceof ApiRoute) {
				$return[] = $router;
			} elseif ($router instanceof RouteList) {
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
		$user = (string) $this->httpAuth['user'];
		$password = (string) $this->httpAuth['password'];

		if ($user === '' || $password === '') {
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
