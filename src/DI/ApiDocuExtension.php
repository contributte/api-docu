<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ApiDocu\DI;

use Nette;

class ApiDocuExtension extends Nette\DI\CompilerExtension
{

	private $defaults = [
		'apiDir' => '%wwwDir%/api'
	];

	/**
	 * @var array
	 */
	protected $config;


	public function loadConfiguration()
	{
		$this->config = $this->_getConfig();
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$router = $builder->getDefinition('router');

		$builder->addDefinition($this->prefix('generator'))
			->setClass('Ublaboo\ApiDocu\Generator')
			->setArguments([$config['apiDir']]);

		$builder->addDefinition($this->prefix('starter'))
			->setClass('Ublaboo\ApiDocu\Starter')
			->setArguments([
				$builder->getDefinition($this->prefix('generator')),
				$builder->getDefinition('router')
			])->addTag('run');
	}


	private function _getConfig()
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		$config['apiDir'] = Nette\DI\Helpers::expand(
			$config['apiDir'],
			$this->getContainerBuilder()->parameters
		);

		return $config;
	}

}
