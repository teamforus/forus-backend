<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\NoReturn;

abstract class BaseCommand extends Command
{
    /**
     * @param string $header
     * @param int $headerType
     * @return void
     */
    protected function printHeader(string $header = '', int $headerType = 3): void
    {
        echo str_repeat('#', $headerType) . " $header  \n";
    }

    /**
     * @param string $text
     * @return void
     */
    protected function printText(string $text = ''): void
    {
        echo "$text  \n";
    }

    /**
     * @param string[] $list
     * @param int $depth
     * @return void
     */
    protected function printList(array $list = [], int $depth = 0): void
    {
        foreach ($list as $item) {
            if (is_string($item)) {
                echo str_repeat("    ", $depth) . " - $item  \n";
            }

            if (is_array($item)) {
                echo str_repeat("    ", $depth) . " - $item[0]  \n";
                $this->printList($item[1], $depth + 1);
            }
        }
    }

    /**
     * @param string $char
     * @return void
     */
    protected function printSeparator(string $char = "="): void
    {
        echo str_repeat($char, 80) . "\n";
    }

    /**
     * @param Collection|Model[] $cards
     * @return void
     */
    protected function printModels(Collection $cards, ...$only)
    {
        $only = is_array($only[0] ?? null) ? $only[0] : $only;
        $only = is_array($only) ? $only : null;

        $body = $cards->map(function(Model $model) use ($only) {
            return $only ? $model->only($only) : $model->attributesToArray();
        })->toArray();

        $this->table($only ?: array_keys($cards[0]->attributesToArray() ?? []), $body);
        $this->printSeparator();
        $this->printText();
    }

    /**
     * @param string $text
     * @return string
     */
    protected function blue(string $text): string
    {
        return "\e[0;34m$text\e[0m";
    }

    /**
     * @param string $text
     * @return string
     */
    public function green(string $text): string
    {
        return "\e[0;32m$text\e[0m";
    }

    /**
     * @param string $text
     * @return string
     */
    public function yellow(string $text): string
    {
        return "\e[0;33m$text\e[0m";
    }

    /**
     * @param string $text
     * @return string
     */
    public function red(string $text): string
    {
        return "\e[0;31m$text\e[0m";
    }

    /**
     * @return void
     */
    protected function exit(): void
    {
        $this->printSeparator();
        echo "Bye!  \n\n";
        exit();
    }
}
