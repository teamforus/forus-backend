<?php

namespace App\Services\Markdown\inline;

/**
 * Adds strikeout inline elements
 */
trait StrikeoutTrait
{
	/**
	 * Parses the strikethrough feature.
	 * @marker ~~
	 */
	protected function parseStrike($markdown)
	{
		if (preg_match('/^~~(.+?)~~/', $markdown, $matches)) {
			return [
				[
					'strike',
					$this->parseInline($matches[1])
				],
				strlen($matches[0])
			];
		}
		return [['text', $markdown[0] . $markdown[1]], 2];
	}

	protected function renderStrike($block)
	{
		return '<del>' . $this->renderAbsy($block[1]) . '</del>';
	}

    abstract protected function parseInline($text);
    abstract protected function renderAbsy($blocks);
}
