<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder\DI;

use Nette\DI\CompilerExtension;
use WebChemistry\AssetsBuilder\AssetsBuilder;
use WebChemistry\AssetsBuilder\Nonce\INonceProvider;
use WebChemistry\AssetsBuilder\Nonce\NonceProvider;

final class AssetsBuilderExtension extends CompilerExtension {

	/** @var mixed[] */
	public $defaults = [
		'css' => [],
		'js' => [],
		'nonceProvider' => NonceProvider::class
	];

	public function loadConfiguration(): void {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$builder->addDefinition($this->prefix('nonceProvider'))
			->setType(INonceProvider::class)
			->setFactory($config['nonceProvider']);

		$assets = $builder->addDefinition($this->prefix('assetsBuilder'))
			->setFactory(AssetsBuilder::class);

		foreach ($config['css'] as $css) {
			$assets->addSetup('addCss', [$css]);
		}
		foreach ($config['js'] as $js) {
			$assets->addSetup('addJs', [$js]);
		}
	}

}
