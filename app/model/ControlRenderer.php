<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\Control;
use Nette\ComponentModel\IComponent;
use Nette\DI\Container;

final class ControlRenderer extends Control
{

	private Container $context;

	/** @var string[] */
	private array $mapping = [];

	public function __construct(Container $context)
	{
		$this->context = $context;
	}

	public function addMapping(string $name, string $class): void
	{
		$this->mapping[$name] = $class;
	}

	/**
	 * @param mixed[] $args
	 */
	public function link(string $destination, array $args = []): string
	{
		return $this->context->getByType(LinkGenerator::class)->link($destination, $args);
	}

	protected function createComponent(string $name): ?IComponent
	{
		return isset($this->mapping[$name]) ? $this->context->getByType($this->mapping[$name]) : parent::createComponent($name);
	}

}
