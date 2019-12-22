<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder\Traits;

use WebChemistry\AssetsBuilder\IAssetsBuilder;

trait TAssetsPreload {

	final public function injectAssetsPreload(IAssetsBuilder $assetsBuilder) {
		$this->onStartup[] = function () use ($assetsBuilder): void {
			if (!$this->isAjax()) {
				$assetsBuilder->preload();
			}
		};
	}

}
