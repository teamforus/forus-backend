<?php

namespace Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\Implementation;
use Tests\Traits\MakesTestFunds;

class MarkdownTableCaptionAndHeadersTest extends DuskTestCase
{
    use MakesTestFunds;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testMarkdownTableReplaceHeadingsAndCaption(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $implementation->funds[0];
        $description  = $fund->description;

        $caption = '1.1 Omnis doloremque et repudiandae doloremque ullam quasi.';
        $header1 = 'header 1';
        $header2 = 'header 2';
        $header3 = 'header 3';

        $fund->update([
            'description' => implode("\n", [
                "#### $caption",
                '',
                '|     |     |     |',
                '| --- | --- | --- |',
                "| $header1 | $header2 | $header3 |",
                '| value 1 | value 2 | value 3 |',
                '',
                'Dolor praesentium in quo et ut expedita. Suscipit ullam saepe provident.',
            ]),
        ]);

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $caption, $header1, $header2, $header3, $description,
        ) {
            $browser->visit($implementation->urlWebshop("/funds/$fund->id"));
            $browser->waitFor('.block.block-markdown table caption');
            $browser->assertMissing('.block.block-markdown h4');
            $browser->assertSeeIn('.block.block-markdown table caption', $caption);
            $browser->assertSeeIn('.block.block-markdown table th:nth-child(1)', $header1);
            $browser->assertSeeIn('.block.block-markdown table th:nth-child(2)', $header2);
            $browser->assertSeeIn('.block.block-markdown table th:nth-child(3)', $header3);

            $fund->update([
                'description' => $description,
            ]);
        });
    }
}
