<?php declare(strict_types = 1);

namespace WebChemistry\AssetsBuilder;

use Nette\StaticClass;
use Nette\Utils\Strings;

final class Utils {

	use StaticClass;

	public static function isAbsoluteUrl(string $url): bool {
		foreach (['//', 'https://', 'http://'] as $item) {
			if (Strings::startsWith($url, $item)) {
				return true;
			}
		}

		return false;
	}

}
