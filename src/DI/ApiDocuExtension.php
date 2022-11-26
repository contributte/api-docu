<?php

declare(strict_types=1);

namespace Contributte\ApiDocu\DI;

use Contributte\ApiDocu\Generator;
use Contributte\ApiDocu\Starter;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\PhpGenerator\ClassType;

final class ApiDocuExtension extends CompilerExtension
{

	/**
	 * @var array<mixed>
	 */
	protected $config;

	/**
	 * @var array<string|array<null>>
	 */
	private $defaults = [
		'apiDir' => '%wwwDir%/api',
		'httpAuth' => [
			'user' => null,
			'password' => null,
		],
	];


	public function loadConfiguration(): void
	{
		$this->config = $this->prepareConfig();
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$builder->addDefinition($this->prefix('generator'))
			->setClass(Generator::class)
			->setArguments([$config['apiDir'], $config['httpAuth']]);

		$builder->addDefinition($this->prefix('starter'))
			->setClass(Starter::class)
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


	/**
	 * @return array<mixed>
	 */
	protected function prepareConfig(): array
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		$config['apiDir'] = Helpers::expand(
			$config['apiDir'],
			$this->getContainerBuilder()->parameters
		);

		return $config;
	}
}
