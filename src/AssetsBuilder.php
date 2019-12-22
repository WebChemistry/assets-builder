<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder;

use Nette\Application\IPresenter;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\SmartObject;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use WebChemistry\AssetsBuilder\Nonce\INonceProvider;

class AssetsBuilder implements IAssetsBuilder {

	use SmartObject;

	/** @var string[] */
	protected $css = [];

	/** @var string[] */
	protected $js = [];

	/** @var INonceProvider */
	private $nonceProvider;

	/** @var IRequest */
	private $request;

	/** @var IResponse */
	private $response;

	public function __construct(INonceProvider $nonceProvider, IRequest $request, IResponse $response) {
		$this->nonceProvider = $nonceProvider;
		$this->request = $request;
		$this->response = $response;
	}

	public function addCss(string $css, ?bool $absolute = null): IAssetsBuilder {
		if ($absolute === false || ($absolute === null && !Utils::isAbsoluteUrl($css))) {
			$css = $this->request->getUrl()->getBasePath() . ltrim($css, '/');
		}

		$this->css[] = $css;

		return $this;
	}

	public function addJs(string $js, ?bool $absolute = null): IAssetsBuilder {
		if ($absolute === false || ($absolute === null && !Utils::isAbsoluteUrl($js))) {
			$js = $this->request->getUrl()->getBasePath() . ltrim($js, '/');
		}

		$this->js[] = $js;

		return $this;
	}

	public function preload(): void {
		foreach ($this->css as $css) {
			$this->response->addHeader('Link', "<$css>; rel=preload; as=style");
		}
		foreach ($this->js as $js) {
			$this->response->addHeader('Link', "<$js>; rel=preload; as=script");
		}
	}

	public function createPreloadAnchor(IPresenter $presenter): void {
		$presenter->onRender[] = function (IPresenter $presenter): void {
			if (!$presenter->isAjax()) {
				$this->preload();
			}
		};
	}

	public function buildJs(): Html {
		$nonce = $this->nonceProvider->getNonce();

		$wrapper = Html::el();
		foreach ($this->js as $js) {
			$wrapper->create('script', [
				'nonce' => $nonce,
				'src' => $js,
			]);
		}

		return $wrapper;
	}

	public function buildCss(): Html {
		$nonce = $this->nonceProvider->getNonce();

		$wrapper = Html::el();
		foreach ($this->css as $css) {
			$child = $wrapper->create('link', [
				'nonce' => $nonce,
				'rel' => 'stylesheet',
				'href' => $css,
			]);
		}

		return $wrapper;
	}

}
