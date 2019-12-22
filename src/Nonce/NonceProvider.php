<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder\Nonce;

use Nette\Http\IResponse;
use Nette\SmartObject;

final class NonceProvider implements INonceProvider {

	use SmartObject;

	/** @var IResponse */
	private $response;

	/** @var string|null */
	private $nonce;

	/** @var bool */
	private $try = false;

	public function __construct(IResponse $response) {
		$this->response = $response;
	}

	public function getNonce(): ?string {
		if ($this->nonce === null && $this->try === false) {
			$this->try = true;

			$header = $this->response->getHeader('Content-Security-Policy') ?:
				$this->response->getHeader('Content-Security-Policy-Report-Only');

			if ($header && preg_match('#\s\'nonce-([\w+/]+=*)\'#', $header, $m)) {
				$this->nonce = $m[1];
			}
		}

		return $this->nonce;
	}

}
