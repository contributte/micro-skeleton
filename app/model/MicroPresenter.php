<?php declare(strict_types = 1);

namespace App\Model;

use InvalidArgumentException;
use Latte\Loaders\StringLoader;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Responses\VoidResponse;
use Nette\Application\UI\Component;
use Nette\Application\UI\ComponentReflection;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\Container;
use Nette\Http\IRequest as IHttpRequest;
use Nette\Http\IResponse as IHttpResponse;
use Nette\InvalidStateException;
use Nette\Routing\Router;
use Nette\Utils\Callback;

class MicroPresenter extends Component implements IPresenter
{

	/** @var Container */
	private $context;

	/** @var IHttpRequest */
	private $httpRequest;

	/** @var IRouter */
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

	protected function getTemplateFactory(): ITemplateFactory
	{
		return $this->getContext()->getByType(ITemplateFactory::class);
	}

	protected function getControlRenderer(): ControlRenderer
	{
		if (!$this->controlRenderer) {
			$this->controlRenderer = $this->getContext()->getByType(ControlRenderer::class);
			$this->controlRenderer->setParent($this);
		}

		return $this->controlRenderer;
	}

	public function run(Request $request): IResponse
	{
		$this->request = $request;

		if ($this->httpRequest && $this->router && !$this->httpRequest->isAjax() && ($request->isMethod('get') || $request->isMethod('head'))) {
			$refUrl = $this->httpRequest->getUrl()->withoutUserInfo();
			$url = $this->router->constructUrl($request->toArray(), $refUrl);
			if ($url !== null && !$refUrl->isEqual($url)) {
				return new Responses\RedirectResponse($url, Http\IResponse::S301_MOVED_PERMANENTLY);
			}
		}

		$params = $request->getParameters();
		if (!isset($params['callback'])) {
			throw new InvalidStateException('Parameter callback is missing.');
		}
		$callback = $params['callback'];
		$reflection = Callback::toReflection(Callback::check($callback));

		if ($this->context) {
			foreach ($reflection->getParameters() as $param) {
				if ($param->getClass()) {
					$params[$param->getName()] = $this->context->getByType($param->getClass()->getName(), false);
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
			if (!$templateSource instanceof \SplFileInfo) {
				$response->getLatte()->setLoader(new StringLoader);
			}
			$response->setFile((string) $templateSource);
		}
		if ($response instanceof ITemplate) {
			return new TextResponse($response);
		} else {
			return $response ?: new VoidResponse;
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

	public function error(string $message = null, int $httpCode = IHttpResponse::S404_NOT_FOUND): void
	{
		throw new BadRequestException($message, $httpCode);
	}

}
