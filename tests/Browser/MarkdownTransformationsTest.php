<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Implementation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class MarkdownTransformationsTest extends DuskTestCase
{
    use MakesTestFunds;

    /**
     * @throws Throwable
     * @return void
     */
    public function testMarkdownTableReplaceHeadingsAndCaption(): void
    {
        [
            'implementation' => $implementation,
            'fund' => $fund,
            'description' => $description,
            'caption' => $caption,
            'headers' => $headers,
        ] = $this->prepareMarkdownTestData();

        $this->browse(function (Browser $browser) use ($implementation, $fund, $caption, $headers, $description) {
            try {
                $browser->visit($implementation->urlWebshop("/funds/$fund->id"));
                $browser->waitFor('.block.block-markdown table caption');
                $browser->assertMissing('.block.block-markdown h4');
                $browser->assertSeeIn('.block.block-markdown table caption', $caption);
                $browser->assertSeeIn('.block.block-markdown table th:nth-child(1)', $headers[0]);
                $browser->assertSeeIn('.block.block-markdown table th:nth-child(2)', $headers[1]);
                $browser->assertSeeIn('.block.block-markdown table th:nth-child(3)', $headers[2]);
            } finally {
                $this->restoreFundDescription($fund, $description);
            }
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMarkdownTableWrapAndResponsiveAttributes(): void
    {
        [
            'implementation' => $implementation,
            'fund' => $fund,
            'description' => $description,
            'headers' => $headers,
        ] = $this->prepareMarkdownTestData();

        $this->browse(function (Browser $browser) use ($implementation, $fund, $description, $headers) {
            try {
                $browser->visit($implementation->urlWebshop("/funds/$fund->id"));
                $browser->waitFor('.block.block-markdown table caption');
                $browser->assertMissing('.block.block-markdown h4');
                $browser->assertPresent('.block.block-markdown .table-wrap');
                $browser->assertPresent('.block.block-markdown .table-wrap table.table-responsive');
                $browser->assertAttribute(
                    '.block.block-markdown table tbody tr:nth-child(1) td:nth-child(1)',
                    'data-title',
                    $headers[0],
                );
            } finally {
                $this->restoreFundDescription($fund, $description);
            }
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMarkdownUpdatesLinkTargetsBasedOnOrigin(): void
    {
        [
            'implementation' => $implementation,
            'fund' => $fund,
            'description' => $description,
            'internalLink' => $internalLink,
            'externalLink' => $externalLink,
        ] = $this->prepareMarkdownTestData();

        $this->browse(function (Browser $browser) use ($implementation, $fund, $description, $internalLink, $externalLink) {
            try {
                $browser->visit($implementation->urlWebshop("/funds/$fund->id"));
                $browser->waitFor('.block.block-markdown table caption');
                $browser->assertMissing('.block.block-markdown h4');
                $browser->waitFor(".block.block-markdown a[href='$externalLink']");
                $browser->assertAttribute(".block.block-markdown a[href='$internalLink']", 'target', '_self');
                $browser->assertAttribute(".block.block-markdown a[href='$internalLink']", 'rel', '');
                $browser->assertAttribute(".block.block-markdown a[href='$externalLink']", 'target', '_blank');
                $browser->assertAttribute(
                    ".block.block-markdown a[href='$externalLink']",
                    'rel',
                    'noopener noreferrer',
                );
            } finally {
                $this->restoreFundDescription($fund, $description);
            }
        });
    }

    private function prepareMarkdownTestData(): array
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $implementation->funds[0];
        $description = $fund->description;

        $caption = '1.1 Omnis doloremque et repudiandae doloremque ullam quasi.';
        $headers = ['header 1', 'header 2', 'header 3'];
        $internalLinkText = 'Internal link';
        $externalLinkText = 'External link';
        $internalLink = "/funds/$fund->id";
        $externalLink = 'https://example.com/markdown-table-caption-test';

        $fund->update([
            'description' => implode("\n", [
                "#### $caption",
                '',
                '|     |     |     |',
                '| --- | --- | --- |',
                '| ' . implode(' | ', $headers) . ' |',
                '| value 1 | value 2 | value 3 |',
                '',
                "[$internalLinkText]($internalLink)",
                '',
                "[$externalLinkText]($externalLink)",
                '',
                'Dolor praesentium in quo et ut expedita. Suscipit ullam saepe provident.',
            ]),
        ]);

        return compact(
            'implementation',
            'fund',
            'description',
            'caption',
            'headers',
            'internalLink',
            'externalLink',
        );
    }

    /**
     * @param Fund $fund
     * @param string $description
     * @return void
     */
    private function restoreFundDescription(Fund $fund, string $description): void
    {
        $fund->update([
            'description' => $description,
        ]);
    }
}
