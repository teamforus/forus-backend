<?php

namespace App\Services\Forus\TestData\FakeGenerators;

use Faker\Generator;

class MarkdownPageGenerator
{
    protected Generator $faker;

    /**
     * Constructor.
     *
     * @param Generator $faker A Faker instance configured for desired locale
     */
    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Generate full formatted markdown content (headers, lists, formatting).
     *
     * @param int $maxLength Maximum allowed content length
     * @return string Generated formatted markdown content
     */
    public function generate(int $maxLength = 4000): string
    {
        $content = '';
        $sections = rand(3, 7);

        for ($i = 0; $i < $sections; $i++) {
            $heading = $this->generateHeading(2);

            if ($this->wouldExceedLength($heading, $maxLength, mb_strlen($content))) {
                break;
            }

            $content .= $heading;

            for ($j = 0, $paraCount = rand(1, 2); $j < $paraCount; $j++) {
                $paragraph = $this->generateFormattedParagraph();

                if ($this->wouldExceedLength($paragraph, $maxLength, mb_strlen($content))) {
                    break 2;
                }

                $content .= $paragraph;
            }

            if (rand(0, 1)) {
                $heading3 = $this->generateHeading(3);

                if ($this->wouldExceedLength($heading3, $maxLength, mb_strlen($content))) {
                    break;
                }

                $content .= $heading3;
            }

            if (rand(0, 1)) {
                $list = $this->generateList();

                if ($this->wouldExceedLength($list, $maxLength, mb_strlen($content))) {
                    break;
                }

                $content .= $list;
            }

            if (rand(0, 1)) {
                $heading4 = $this->generateHeading(4);

                if ($this->wouldExceedLength($heading4, $maxLength, mb_strlen($content))) {
                    break;
                }

                $content .= $heading4;
            }

            $closingParagraph = $this->generateParagraph() . PHP_EOL . PHP_EOL;

            if ($this->wouldExceedLength($closingParagraph, $maxLength, mb_strlen($content))) {
                break;
            }

            $content .= $closingParagraph;
        }

        return $content;
    }

    /**
     * Generate a single plain paragraph consisting of 3–6 sentences.
     *
     * @return string Generated paragraph text
     */
    protected function generateParagraph(): string
    {
        $sentences = [];
        $sentenceCount = rand(3, 6);

        for ($i = 0; $i < $sentenceCount; $i++) {
            $wordCount = rand(8, 20);
            $sentence = ucfirst(implode(' ', $this->faker->words($wordCount))) . '.';
            $sentences[] = $sentence;
        }

        return implode(' ', $sentences);
    }

    /**
     * Generate a paragraph with optional inline markdown formatting (bold, italic, code, links).
     *
     * @return string Generated formatted paragraph text
     */
    protected function generateFormattedParagraph(): string
    {
        $paragraph = $this->generateParagraph();

        return preg_replace_callback('/\b(\w{6,})\b/', function ($matches) {
            $word = $matches[1];

            return match (rand(0, 20)) {
                0 => "**$word**",
                1 => "*$word*",
                2 => "`$word`",
                3 => '[' . $word . '](https://' . $this->faker->safeEmailDomain() . ')',
                default => $word,
            };
        }, $paragraph) . PHP_EOL . PHP_EOL;
    }

    /**
     * Generate a markdown heading of the specified level (##, ###, ####).
     *
     * @param int $level Heading level (e.g., 2, 3, 4)
     * @return string Generated markdown heading text
     */
    protected function generateHeading(int $level): string
    {
        $wordCount = match ($level) {
            2 => rand(5, 15),
            3, 4 => rand(5, 10),
            default => rand(3, 6),
        };

        return str_repeat('#', $level) . ' ' . ucfirst($this->faker->words($wordCount, true)) . PHP_EOL . PHP_EOL;
    }

    /**
     * Generate a markdown unordered list (3–6 items).
     *
     * @return string Generated markdown list text
     */
    protected function generateList(): string
    {
        $list = '';
        $itemCount = rand(3, 6);

        for ($i = 0; $i < $itemCount; $i++) {
            $sentence = ucfirst(implode(' ', $this->faker->words(rand(8, 14)))) . '.';
            $clean = str_replace(['*', '`', '_', '[', ']'], '', $sentence);
            $list .= '- ' . $clean . PHP_EOL;
        }

        return $list . PHP_EOL;
    }

    /**
     * Determine if appending the given text would exceed the maximum allowed length.
     *
     * @param string $text Text to check
     * @param int $maxLength Maximum allowed content length
     * @param int $length Current content length
     * @return bool True if adding the text would exceed maxLength, false otherwise
     */
    protected function wouldExceedLength(string $text, int $maxLength, int $length): bool
    {
        return ($length + mb_strlen($text)) > $maxLength;
    }
}
