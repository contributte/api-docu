<?php declare(strict_types = 1);

namespace Contributte\ApiDocu;

class TemplateFilters
{

	public static function common(string $filter): ?string
	{
		if (method_exists(self::class, $filter)) {
			$args = func_get_args();
			array_shift($args);

			$callback = [self::class, $filter];

			if (!is_callable($callback)) {
				throw new \UnexpectedValueException();
			}

			return ($callback)($args);
		}

		return null;
	}

	/**
	 * @param array<mixed> $text
	 */
	public static function description(array $text): string
	{
		$text = reset($text);
		$text = nl2br($text); // @phpstan-ignore-line
		$text = str_replace(["\n", "\n\r", "\r\n", "\r"], '', $text);

		$text = preg_replace_callback('/<json><br \/>(.*?)<\/json>/s', function ($item): string {
			$s = '<br><pre class="apiDocu-json">' . str_replace('<br>', '', (string) end($item)) . '</pre>';
			$s = (string) preg_replace('/(\s)"([^"]+)"/', '$1<span class="apiDocu-string">"$2"</span>', $s);
			$s = (string) preg_replace('/\/\/(.*?)<br \/>/', '<span class="apiDocu-comment">//$1</span><br>', $s);

			return $s;
		}, $text);

		$text = preg_replace('/\*\*([^*]*)\*\*/', '<strong>$1</strong>', (string) $text);

		return (string) $text;
	}

}
