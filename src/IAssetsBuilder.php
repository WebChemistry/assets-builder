<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder;

use Nette\Utils\Html;

interface IAssetsBuilder {

	public function addCss(string $css): IAssetsBuilder;

	public function addJs(string $js): IAssetsBuilder;

	public function buildJs(): Html;

	public function buildCss(): Html;

	public function preload(): void;

}
