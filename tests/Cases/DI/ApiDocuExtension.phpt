<?php declare(strict_types = 1);

use Contributte\ApiDocu\DI\ApiDocuExtension;
use Contributte\ApiDocu\Generator;
use Contributte\ApiRouter\DI\ApiRouterExtension;
use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Nette\Bridges\ApplicationDI\LatteExtension;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$loader = new ContainerLoader(Environment::getTestDir(), true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('apiRouter', new ApiRouterExtension());
		$compiler->addExtension('apiDocu', new ApiDocuExtension());
		$compiler->addExtension('http', new HttpExtension());
		$compiler->addExtension('latte', new LatteExtension(Environment::getTestDir()));

		$compiler->addConfig([
			'parameters' => [
				'wwwDir' => Environment::getTestDir(),
				'tempDir' => Environment::getTestDir(),
				'debugMode' => false,
			],
		]);
		$compiler->addConfig(\Contributte\Tester\Utils\Neonkit::load(<<<'NEON'
		services:
			router: Nette\Routing\SimpleRouter
		NEON
		));
	});

	/** @var Container $container */
	$container = new $class();

	Assert::type(Generator::class, $container->getByType(Generator::class));
});
