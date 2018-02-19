<?php

namespace App\Model;

use Latte\Loaders\StringLoader;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Component;
use Nette\Application\UI\ComponentReflection;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\Container;
use Nette\Http\IRequest as IHttpRequest;
use Nette\Http\IResponse as IHttpResponse;
use Nette\Utils\Callback;
use SplFileInfo;

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

	/**
	 * @param Container $context
	 * @param IHttpRequest $httpRequest
	 * @param IRouter $router
	 */
	public function __construct(Container $context, IHttpRequest $httpRequest, IRouter $router)
	{
		parent::__construct();
		$this->context = $context;
		$this->httpRequest = $httpRequest;
		$this->router = $router;
	}

	/**
	 * @return Container
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return ITemplateFactory
	 */
	protected function getTemplateFactory()
	{
		return $this->getContext()->getByType(ITemplateFactory::class);
	}

	/**
	 * @return ControlRenderer
	 */
	protected function getControlRenderer()
	{
		if (!$this->controlRenderer) {
			$this->controlRenderer = $this->getContext()->getByType(ControlRenderer::class);
			$this->controlRenderer->setParent($this);
		}

		return $this->controlRenderer;
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * @return IResponse
	 */
	public function run(Request $request)
	{
		$this->request = $request;

		if ($this->httpRequest && $this->router && !$this->httpRequest->isAjax() && ($request->isMethod('get') || $request->isMethod('head'))) {
			$refUrl = clone $this->httpRequest->getUrl();
			$url = $this->router->constructUrl($request, $refUrl->setPath($refUrl->getScriptPath()));
			if ($url !== NULL && !$this->httpRequest->getUrl()->isEqual($url)) {
				return new RedirectResponse($url, IHttpResponse::S301_MOVED_PERMANENTLY);
			}
		}

		$params = $request->getParameters();
		if (!isset($params['callback'])) {
			throw new BadRequestException('Parameter callback is missing.');
		}

		$callback = $params['callback'];
		$reflection = Callback::toReflection(Callback::check($callback));

		if ($this->context) {
			foreach ($reflection->getParameters() as $param) {
				if ($param->getClass()) {
					$params[$param->getName()] = $this->context->getByType($param->getClass()->getName(), FALSE);
				}
			}
		}
		$params['presenter'] = $this;
		$params = ComponentReflection::combineArgs($reflection, $params);

		$response = call_user_func_array($callback, $params);

		if (is_string($response)) {
			$response = [$response, []];
		}
		if (is_array($response)) {
			list($templateSource, $templateParams) = $response;
			$response = $this->createTemplate()->setParameters($templateParams);
			if (!$templateSource instanceof SplFileInfo) {
				$response->getLatte()->setLoader(new StringLoader);
			}
			$response->setFile($templateSource);
		}
		if ($response instanceof ITemplate) {
			return new TextResponse($response);
		} else {
			return $response;
		}
	}

	/**
	 * @return Template
	 */
	public function createTemplate()
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

	/**
	 * @param string $url
	 * @param int $httpCode
	 * @return RedirectResponse
	 */
	public function redirectUrl($url, $httpCode = IHttpResponse::S302_FOUND)
	{
		return new RedirectResponse($url, $httpCode);
	}

	/**
	 * @param string $message
	 * @param int $httpCode
	 * @throws BadRequestException
	 */
	public function error($message = NULL, $httpCode = IHttpResponse::S404_NOT_FOUND)
	{
		throw new BadRequestException($message, $httpCode);
	}

}
