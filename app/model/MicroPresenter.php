<?php declare(strict_types = 1);

namespace App\Model;

use Latte\Loaders\StringLoader;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Responses\VoidResponse;
use Nette\Application\UI\Component;
use Nette\Application\UI\ComponentReflection;
use Nette\Application\UI\Template;
use Nette\DI\Container;
use Nette\Http\IRequest as IHttpRequest;
use Nette\Http\IResponse;
use Nette\Http\IResponse as IHttpResponse;
use Nette\InvalidArgumentException;
use Nette\Routing\Router;
use Nette\SmartObject;
use Nette\Utils\Callback;
use SplFileInfo;

final class MicroPresenter extends Component implements IPresenter
{

	use SmartObject;

	/** @var Container */
	private $context;

	/** @var IHttpRequest */
	private $httpRequest;

	/** @var Router */
	private $router;

	/** @var Request */
	private $request;

	/** @var ControlRenderer */
	private $controlRenderer;

	public function __construct(Container $context, IHttpRequest $httpRequest, Router $router)
	{
		$this->context = $context;
		$this->httpRequest = $httpRequest;
		$this->router = $router;
	}

	public function getContext(): Container
	{
		return $this->context;
	}

	public function getRequest(): Request
	{
		return $this->request;
	}

	protected function getTemplateFactory(): TemplateFactory
	{
		return $this->getContext()->getByType(TemplateFactory::class);
	}

	protected function getControlRenderer(): ControlRenderer
	{
		if (!$this->controlRenderer) {
			$this->controlRenderer = $this->getContext()->getByType(ControlRenderer::class);
			$this->controlRenderer->setParent($this);
		}

		return $this->controlRenderer;
	}

	public function run(Request $request): Response
	{
		$this->request = $request;

		if (
			$this->httpRequest
			&& $this->router
			&& !$this->httpRequest->isAjax()
			&& ($request->isMethod('get') || $request->isMethod('head'))
		) {
			$refUrl = $this->httpRequest->getUrl()->withoutUserInfo();
			$url = $this->router->constructUrl($request->toArray(), $refUrl);
			if ($url !== null && !$refUrl->isEqual($url)) {
				return new RedirectResponse($url, IResponse::S301_MOVED_PERMANENTLY);
			}
		}

		$params = $request->getParameters();
		$callback = $params['callback'] ?? null;

		if (!is_object($callback) || !is_callable($callback)) {
			throw new BadRequestException('Parameter callback is not a valid closure.');
		}

		$reflection = Callback::toReflection($callback);

		if ($this->context) {
			foreach ($reflection->getParameters() as $param) {
				if ($param->getType()) {
					$params[$param->getName()] = $this->context->getByType($param->getType()->getName(), false);
				}
			}
		}

		$params['presenter'] = $this;

		try {
			$params = ComponentReflection::combineArgs($reflection, $params);
		} catch (InvalidArgumentException $e) {
			$this->error($e->getMessage());
		}

		$response = $callback(...array_values($params));

		if (is_string($response)) {
			$response = [$response, []];
		}

		if (is_array($response)) {
			[$templateSource, $templateParams] = $response;
			$response = $this->createTemplate()->setParameters($templateParams);

			if (!$templateSource instanceof SplFileInfo) {
				$response->getLatte()->setLoader(new StringLoader());
			}

			$response->setFile((string) $templateSource);
		}

		if ($response instanceof Template) {
			return new TextResponse($response);
		} else {
			return $response ?: new VoidResponse();
		}
	}

	public function createTemplate(): Template
	{
		/** @var Template $template */
		$template = $this->getTemplateFactory()->createTemplate();
		$template->setParameters($this->request->getParameters());

		// Control renderer
		$control = $this->getControlRenderer();
		$template->control = $control;
		$template->presenter = $control;
		$template->getLatte()->addProvider('uiControl', $control);
		$template->getLatte()->addProvider('uiPresenter', $control);

		return $template;
	}

	public function redirectUrl(string $url, int $httpCode = IHttpResponse::S302_FOUND): RedirectResponse
	{
		return new RedirectResponse($url, $httpCode);
	}


	public function error(string $message = '', int $httpCode = IHttpResponse::S404_NOT_FOUND): void
	{
		throw new BadRequestException($message, $httpCode);
	}

}
