<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder\Nonce;

interface INonceProvider {

	public function getNonce(): ?string;

}
