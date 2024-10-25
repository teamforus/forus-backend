<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReimbursementsSponsorExport extends BaseFieldedExport
{
    protected Collection $data;
    protected array $fields;

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'code' => 'NR',
        'implementation_name' => 'Implementatie',
        'fund_name' => 'Fonds',
        'amount' => 'Bedrag',
        'employee' => 'Medewerker',
        'email' => 'E-mail',
        'bsn'  => 'BSN',
        'iban' => 'IBAN',
        'iban_name' => 'Tenaamstelling',
        'provider_name' => 'Aanbieder',
        'category' => 'Categorie',
        'title' => 'Title',
        'description' => 'Explanation',
        'files_count' => 'Receipt/invoice count',
        'lead_time' => 'Afhandeltijd',
        'submitted_at' => 'Submitted at',
        'resolved_at' => 'Resolved at',
        'expired' => 'Verlopen',
        'state' => 'Status',
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, array $fields)
    {
        $this->data = Reimbursement::export($request, $organization, $fields);
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $collection = $this->collection();

        return array_map(
            fn ($key) => static::$exportFields[$key] ?? $key,
            $collection->isNotEmpty() ? array_keys($collection->first()) : $this->fields
        );
    }
}
