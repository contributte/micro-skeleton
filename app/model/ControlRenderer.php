<?php

namespace App\Model;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Control;
use Nette\ComponentModel\IComponent;
use Nette\DI\Container;

final class ControlRenderer extends Control
{

	/** @var Container */
	private $context;

	/** @var array */
	private $mapping = [];

	/**
	 * @param Container $context
	 */
	public function __construct(Container $context)
	{
		parent::__construct();
		$this->context = $context;
	}

	/**
	 * @param string $name
	 * @param string $class
	 */
	public function addMapping($name, $class)
	{
		$this->mapping[$name] = $class;
	}

	/**
	 * @param string $name
	 * @return IComponent
	 */
	protected function createComponent($name)
	{
		if (isset($this->mapping[$name])) {
			return $this->context->getByType($this->mapping[$name]);
		} else {
			return parent::createComponent($name);

		}
	}

	/**
	 * @param string $destination
	 * @param array $args
	 * @return string
	 */
	public function link($destination, $args = [])
	{
		return $this->context->getByType(LinkGenerator::class)->link($destination, $args);
	}

}
