<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Control;
use Nette\ComponentModel\IComponent;
use Nette\DI\Container;

final class ControlRenderer extends Control
{

	/** @var Container */
	private $context;

	/** @var string[] */
	private $mapping = [];

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function addMapping(string $name, string $class): void
	{
		$this->mapping[$name] = $class;
	}

	protected function createComponent(string $name): ?IComponent
	{
		if (isset($this->mapping[$name])) {
			return $this->context->getByType($this->mapping[$name]);
		} else {
			return parent::createComponent($name);

		}
	}

	/**
	 * @param mixed[] $args
	 */
	public function link(string $destination, array $args = []): string
	{
		return $this->context->getByType(LinkGenerator::class)->link($destination, $args);
	}

}
