<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder\DI;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Json;
use WebChemistry\AssetsBuilder\AssetsBuilder;
use WebChemistry\AssetsBuilder\AssetsBuilderException;
use WebChemistry\AssetsBuilder\AssetsBuilderManager;
use WebChemistry\AssetsBuilder\Nonce\INonceProvider;
use WebChemistry\AssetsBuilder\Nonce\NonceProvider;
use Webmozart\Assert\Assert;

final class AssetsBuilderExtension extends CompilerExtension {

	/** @var mixed[] */
	private $manifests = [];

	public function getConfigSchema(): Schema {
		return Expect::structure([
			'manifests' => Expect::arrayOf(
				Expect::structure([
					'manifest' => Expect::string(),
					'styles' => Expect::arrayOf('string')->default([]),
					'javascript' => Expect::arrayOf('string')->default([]),
				])
			),
			'enabled' => Expect::bool(true),
			'nonceProvider' => Expect::string(NonceProvider::class),
			'sources' => Expect::arrayOf(
				Expect::structure([
					'css' => Expect::arrayOf('string')->default([]),
					'js' => Expect::arrayOf('string')->default([]),
				])
			)
		]);
	}

	public function loadConfiguration(): void {
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		if (!$config->enabled) {
			return;
		}

		$builder->addDefinition($this->prefix('nonceProvider'))
			->setType(INonceProvider::class)
			->setFactory($config->nonceProvider);

		$manager = $builder->addDefinition($this->prefix('assetsBuilderManager'))
			->setType(AssetsBuilderManager::class);

		foreach ($config->manifests as $name => $info) {
			$this->processManifest($name, $info);

			$builder->addDependency($info->manifest);
		}

		$first = true;
		foreach ($config->sources as $name => $info) {
			$assets = $builder->addDefinition($this->prefix($name))
				->setFactory(AssetsBuilder::class);

			if (!$first) {
				$assets->setAutowired(false);
			}

			foreach ($info->css as $css) {
				$css = $this->findFromManifest($css, 'css');
				$assets->addSetup('addCss', [$css]);
			}
			foreach ($info->js as $js) {
				$js = $this->findFromManifest($js, 'js');
				$assets->addSetup('addJs', [$js]);
			}

			$manager->addSetup('addBuilder', [$name, $assets]);

			$first = false;
		}
	}

	protected function processManifest(string $name, object $config): void {
		Assert::fileExists($config->manifest, 'Manifest file %s not exists');
		$array = Json::decode(file_get_contents($config->manifest), Json::FORCE_ARRAY);

		$this->manifests[$name] = [
			'css' => [],
			'js' => [],
		];

		foreach ($config->styles as $original => $style) {
			if (!isset($array[$original])) {
				throw new AssetsBuilderException("$original not exists in manifest.");
			}

			$this->manifests[$name]['css'][$original] = str_replace('$name', $array[$original], $style);
		}

		foreach ($config->javascript as $original => $javascript) {
			if (!isset($array[$original])) {
				throw new AssetsBuilderException("$original not exists in manifest.");
			}

			$this->manifests[$name]['js'][$original] =  str_replace('$name', $array[$original], $javascript);;
		}
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

}
