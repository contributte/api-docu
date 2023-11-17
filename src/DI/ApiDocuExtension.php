<?php declare(strict_types = 1);

namespace Contributte\ApiDocu\DI;

use Contributte\ApiDocu\Generator;
use Contributte\ApiDocu\Starter;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

/**
 * @property-read stdClass $config
 */
class ApiDocuExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'apiDir' => Expect::string()->default('%wwwDir%/api'),
			'httpAuth' => Expect::structure([
				'user' => Expect::string(),
				'password' => Expect::string(),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$this->config = $this->prepareConfig();
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$builder->addDefinition($this->prefix('generator'))
			->setType(Generator::class)
			->setArguments([$config->apiDir, (array) $config->httpAuth]);

		$builder->addDefinition($this->prefix('starter'))
			->setType(Starter::class)
			->setArguments([
				$builder->getDefinition($this->prefix('generator')),
				$builder->getDefinition('router'),
			]);
	}

	public function afterCompile(ClassType $class): void
	{
		parent::afterCompile($class);

		$class->getMethod('initialize')->addBody(
			'$this->getService(?);',
			[$this->prefix('starter')]
		);
	}

	protected function prepareConfig(): stdClass
	{
		/** @var stdClass $config */
		$config = $this->getConfig();
		$config->apiDir = Helpers::expand($config->apiDir, $this->getContainerBuilder()->parameters);

		return $config;
	}

}
