<?php

namespace App\Libs\Markdown\Extensions\Youtube;

interface YouTubeUrlParserInterface
{
	/**
	 * @param string $url
	 * @return YouTubeUrlInterface|null
	 */
	public function parse(string $url): ?YouTubeUrlInterface;
}
