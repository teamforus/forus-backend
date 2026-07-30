<?php

namespace Tests\Unit;

use App\Models\FundCriteriaGroup;
use App\Services\TranslationService\Models\TranslationValue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;

class TranslationValueUsageTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testGroupsCmsValueAndFundCriteriaGroupTypesUnderReadableUsageLabels(): void
    {
        $date = Carbon::now()->startOfMonth()->addDay();
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->createTranslationValue($organization->id, 'implementation_cms_block_value', 10, $date);
        $this->createTranslationValue($organization->id, 'implementation_cms_block_item_value', 20, $date);
        $this->createTranslationValue($organization->id, FundCriteriaGroup::class, 30, $date);

        $usage = TranslationValue::getUsage($organization->id, $date->copy(), $date->copy());
        $groups = collect($usage['groups'])->keyBy('name');

        $this->assertSame(30, $groups->get('Webshop content')['symbols']);
        $this->assertSame(30, $groups->get('Aanvraagformulier')['symbols']);
        $this->assertFalse($groups->has('implementation_cms_block_value'));
        $this->assertFalse($groups->has('implementation_cms_block_item_value'));
        $this->assertFalse($groups->has(FundCriteriaGroup::class));
    }

    /**
     * @param int $organizationId
     * @param string $translatableType
     * @param int $symbols
     * @param Carbon $date
     * @return void
     */
    protected function createTranslationValue(
        int $organizationId,
        string $translatableType,
        int $symbols,
        Carbon $date,
    ): void {
        TranslationValue::query()->create([
            'translatable_type' => $translatableType,
            'translatable_id' => 1,
            'key' => 'value',
            'from' => str_repeat('A', $symbols),
            'from_length' => $symbols,
            'to' => str_repeat('B', $symbols),
            'to_length' => $symbols,
            'locale' => 'en-US',
            'organization_id' => $organizationId,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
