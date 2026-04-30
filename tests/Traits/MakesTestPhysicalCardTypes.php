<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundPhysicalCardType;
use App\Models\Organization;
use App\Models\PhysicalCardRequest;
use App\Models\PhysicalCardType;
use App\Models\Voucher;
use App\Traits\DoesTesting;
use Illuminate\Database\Eloquent\Model;

trait MakesTestPhysicalCardTypes
{
    use DoesTesting;

    /**
     * @param Organization $organization
     * @param string|null $name
     * @param string|null $description
     * @param int|null $codeBlocks
     * @param int|null $codeBlockSize
     * @return PhysicalCardType
     */
    protected function makeTestPhysicalCardType(
        Organization $organization,
        ?string $name = null,
        ?string $description = null,
        ?int $codeBlocks = null,
        ?int $codeBlockSize = null,
    ): PhysicalCardType {
        return $organization->physical_card_types()->create([
            'name' => $name ?? $this->faker->name(),
            'description' => $description ?? $this->faker->text(),
            'code_blocks' => $codeBlocks ?? 4,
            'code_block_size' => $codeBlockSize ?? 4,
        ]);
    }

    /**
     * @param Fund $fund
     * @param PhysicalCardType $type
     * @return FundPhysicalCardType|Model
     */
    protected function makeTestFundPhysicalCardType(Fund $fund, PhysicalCardType $type): Model|FundPhysicalCardType
    {
        return $fund->fund_physical_card_types()->create([
            'physical_card_type_id' => $type->id,
        ]);
    }

    /**
     * @param Voucher $voucher
     * @param PhysicalCardType $type
     * @return PhysicalCardRequest|Model
     */
    protected function makeTestFundPhysicalCardRequest(Voucher $voucher, PhysicalCardType $type): Model|PhysicalCardRequest
    {
        return $voucher->makePhysicalCardRequest([
            'address' => $this->faker->address(),
            'house' => $this->faker->buildingNumber(),
            'house_addition' => $this->faker->buildingNumber(),
            'postcode' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'physical_card_type_id' => $type->id,
        ]);
    }
}
