<?php

namespace App\Libs\Markdown\Extensions\Youtube;

interface YouTubeUrlInterface
{
	/**
	 * @return string
	 */
	public function getVideoId(): string;

	/**
	 * @return string|null
	 */
	public function getStartTimestamp(): ?string;
}