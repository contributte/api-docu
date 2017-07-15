<?php

declare(strict_types=1);

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ApiDocu\DI;

use Nette;

class ApiDocuExtension extends Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	protected $config;

	private $defaults = [
		'apiDir' => '%wwwDir%/api',
		'httpAuth' => [
			'user' => null,
			'password' => null,
		],
	];


	/**
	 * @return void
	 */
	public function loadConfiguration()
	{
		$this->config = $this->_getConfig();
	}


	/**
	 * @return void
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$builder->addDefinition($this->prefix('generator'))
			->setClass('Ublaboo\ApiDocu\Generator')
			->setArguments([$config['apiDir'], $config['httpAuth']]);

		$builder->addDefinition($this->prefix('starter'))
			->setClass('Ublaboo\ApiDocu\Starter')
			->setArguments([
				$builder->getDefinition($this->prefix('generator')),
				$builder->getDefinition('router'),
			])->addTag('run');
	}


	/**
	 * @return array
	 */
	protected function _getConfig()
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		$config['apiDir'] = Nette\DI\Helpers::expand(
			$config['apiDir'],
			$this->getContainerBuilder()->parameters
		);

		return $config;
	}
}
