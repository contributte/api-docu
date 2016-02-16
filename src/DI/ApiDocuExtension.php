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
		'liveDocu' => TRUE
	];


	public function loadConfiguration()
	{
		$this->config = $this->_getConfig();
	}


	private function _getConfig()
	{
		$config = $this->validateConfig($this->defaults, $this->config);

		return $config;
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->compiler->getConfig();

		$router = $builder->getDefinition('router');

		$builder->addDefinition($this->prefix('generator'))
			->setClass('Ublaboo\ApiDocu\Generator');

		$builder->addDefinition($this->prefix('starter'))
			->setClass('Ublaboo\ApiDocu\Starter')
			->setArguments([
				$builder->getDefinition($this->prefix('generator')),
				$builder->getDefinition('router')
			])->addTag('run');
	}

}
