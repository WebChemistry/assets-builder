<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder;

use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\SmartObject;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use WebChemistry\Asserts\FileSystemAssert;
use WebChemistry\AssetsBuilder\Nonce\INonceProvider;

class AssetsBuilder implements IAssetsBuilder {

	use SmartObject;

	/** @var string[] */
	protected $css = [];

	/** @var string[] */
	protected $js = [];

	/** @var string[] */
	protected $manifests = [];

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

	public function addManifest(string $name, string $manifest, array $styles, array $javascript): IAssetsBuilder {
		FileSystemAssert::fileExists($manifest);
		$array = Json::decode(file_get_contents($manifest), Json::FORCE_ARRAY);

		$this->manifests[$name] = [
			'css' => [],
			'js' => [],
		];

		foreach ($styles as $original => $style) {
			if (!isset($array[$original])) {
				throw new AssetsBuilderException("$original not exists in manifest.");
			}

			$this->manifests[$name]['css'][$original] = str_replace('$name', $array[$original], $style);
		}

		foreach ($javascript as $original => $javascript) {
			if (!isset($array[$original])) {
				throw new AssetsBuilderException("$original not exists in manifest.");
			}

			$this->manifests[$name]['js'][$original] =  str_replace('$name', $array[$original], $javascript);;
		}

		return $this;
	}

	public function addCss(string $css, ?bool $absolute = null): IAssetsBuilder {
		$css = $this->findFromManifest($css, 'css');

		if ($absolute === false || ($absolute === null && !Utils::isAbsoluteUrl($css))) {
			$css = $this->request->getUrl()->getBasePath() . ltrim($css, '/');
		}

		$this->css[] = $css;

		return $this;
	}

	public function addJs(string $js, ?bool $absolute = null): IAssetsBuilder {
		$js = $this->findFromManifest($js, 'js');

		if ($absolute === false || ($absolute === null && !Utils::isAbsoluteUrl($js))) {
			$js = $this->request->getUrl()->getBasePath() . ltrim($js, '/');
		}

		$this->js[] = $js;

		return $this;
	}

	protected function findFromManifest(string $path, string $section): string {
		if (preg_match('#^\$(\w+)/([\w+\.\-]+)$#', $path, $matches)) {
			if (!isset($this->manifests[$matches[1]])) {
				throw new AssetsBuilderException("Manifest $matches[1] not exists.");
			}
			if (!isset($this->manifests[$matches[1]][$section][$matches[2]])) {
				throw new AssetsBuilderException("Manifest $matches[1] does not have $matches[2].");
			}
			$path = $this->manifests[$matches[1]][$section][$matches[2]];
		}

		return $path;
	}

	public function preload(): void {
		foreach ($this->css as $css) {
			$this->response->addHeader('Link', "<$css>; rel=preload; as=style");
		}
		foreach ($this->js as $js) {
			$this->response->addHeader('Link', "<$js>; rel=preload; as=script");
		}
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
