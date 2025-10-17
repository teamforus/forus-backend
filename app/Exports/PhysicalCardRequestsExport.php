<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\PhysicalCardRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PhysicalCardRequestsExport extends BaseFieldedExport
{
    protected static string $transKey = 'physical_card_requests';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'address',
        'house',
        'house_addition',
        'postcode',
        'city',
    ];

    /**
     * @param int|null $fund_id
     * @param string|null $date
     * @param array $fields
     */
    public function __construct(
        ?int $fund_id = null,
        ?string $date = null,
        protected array $fields = []
    ) {
        $this->data = $this->export($fund_id, $date);
    }

    /**
     * @param int|null $fund_id
     * @param string|null $date
     * @return Collection
     */
    protected function export(?int $fund_id = null, ?string $date = null): Collection
    {
        /** @var Builder $query */
        $query = PhysicalCardRequest::query();

        if ($fund_id) {
            $query->whereHas('voucher', static function (Builder $query) use ($fund_id) {
                $query->where('fund_id', $fund_id);
            });
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $this->exportTransform($query->get());
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (PhysicalCardRequest $physicalCard) => array_only(
            $this->getRow($physicalCard),
            $this->fields,
        ))->values());
    }

    /**
     * @param PhysicalCardRequest $physicalCardRequest
     * @return array
     */
    protected function getRow(PhysicalCardRequest $physicalCardRequest): array
    {
        return [
            ...$physicalCardRequest->only([
                'address', 'house', 'house_addition', 'postcode', 'city',
                'physical_card_type_id', 'fund_request_id', 'voucher_id',
            ]),
            'physical_card_type_name' => $physicalCardRequest->physical_card_type?->name,
        ];
    }
}
