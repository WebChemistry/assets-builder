<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder;

use InvalidArgumentException;
use Nette\Application\IPresenter;
use Nette\SmartObject;
use UnexpectedValueException;

final class AssetsBuilderManager {

	use SmartObject;

	/** @var IAssetsBuilder[] */
	private $builders;

	/** @var string|null */
	private $default;

	public function addBuilder(string $name, IAssetsBuilder $builder): void {
		if ($this->default === null) {
			$this->default = $name;
		}

		$this->builders[$name] = $builder;
	}

	public function getBuilder(string $name): IAssetsBuilder {
		if (!isset($this->builders[$name])) {
			throw new InvalidArgumentException(sprintf('Builder %s not exists', $default));
		}

		return $this->builders[$name];
	}

	public function getDefault(): IAssetsBuilder {
		if (!$this->builders) {
			throw new UnexpectedValueException('Builders are empty');
		}

		return $this->builders[$this->default];
	}

	public function setDefault(string $default): void {
		if (!isset($this->builders[$default])) {
			throw new InvalidArgumentException(sprintf('Builder %s not exists', $default));
		}

		$this->default = $default;
	}

	public function createPreloadAnchor(IPresenter $presenter): void {
		$presenter->onRender[] = function (IPresenter $presenter): void {
			if (!$presenter->isAjax()) {
				$this->getDefault()->preload();
			}
		};
	}

}
