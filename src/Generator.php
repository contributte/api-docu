<?php

/**
 * @copyright   Copyright (c) 2016 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\ApiDocu;

use Nette;
use Nette\Application\Request;
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
	private $template = 'api_docu.latte';

	/**
	 * @var string
	 */
	private $template_target = 'api_docu_matched.latte';


	public function __construct(
		Nette\Application\UI\ITemplateFactory $templateFactory,
		Nette\Http\Request $httpRequest
	) {
		$this->templateFactory = $templateFactory;
		$this->httpRequest = $httpRequest;
	}


	public function generateTarget(ApiRoute $route, Request $request)
	{
		$template = $this->createTemplate($this->template_target);

		$template->route = $route;
		$template->request = $request;
		$template->httpRequest = $this->httpRequest;

		if (class_exists('Tracy\Debugger')) {
			Debugger::$productionMode = TRUE;
		}

		echo (string) $template;
	}


	public function createTemplate($which)
	{
		$template = $this->templateFactory->createTemplate();
		$template->setFile(__DIR__ . '/templates/' . $which);

		$template->base_dir = __DIR__ . '/templates';

		$template->addFilter('routeMaskStyles', function($mask) {
			return str_replace(['<', '>'], ['<span class="apiDocu-mask-param">&lt;', '&gt;</span>'], $mask);
		});

		return $template;
	}

}
