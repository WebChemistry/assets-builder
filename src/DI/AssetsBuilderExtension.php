<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use WebChemistry\AssetsBuilder\AssetsBuilder;
use WebChemistry\AssetsBuilder\Nonce\INonceProvider;
use WebChemistry\AssetsBuilder\Nonce\NonceProvider;

final class AssetsBuilderExtension extends CompilerExtension {

	public function getConfigSchema(): Schema {
		return Expect::structure([
			'css' => Expect::arrayOf('string')->default([]),
			'js' => Expect::arrayOf('string')->default([]),
			'manifests' => Expect::arrayOf(
				Expect::structure([
					'manifest' => Expect::string(),
					'styles' => Expect::arrayOf('string')->default([]),
					'javascript' => Expect::arrayOf('string')->default([]),
				])
			),
			'nonceProvider' => Expect::string(NonceProvider::class),
		]);
	}

	public function loadConfiguration(): void {
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('nonceProvider'))
			->setType(INonceProvider::class)
			->setFactory($config->nonceProvider);

		$assets = $builder->addDefinition($this->prefix('assetsBuilder'))
			->setFactory(AssetsBuilder::class);

		foreach ($config->manifests as $name => $options) {
			$assets->addSetup('addManifest', [$name, $options->manifest, $options->styles, $options->javascript]);
		}
		foreach ($config->css as $css) {
			$assets->addSetup('addCss', [$css]);
		}
		foreach ($config->js as $js) {
			$assets->addSetup('addJs', [$js]);
		}
	}

}
