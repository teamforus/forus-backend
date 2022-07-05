<?php

namespace App\Console\Commands\PhysicalCards;

use App\Console\Commands\BaseCommand;
use App\Models\Fund;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use App\Models\VoucherRelation;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class MigratePhysicalCardsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'physical-cards:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate physical cards from expired vouchers.';

    /**
     * Migration source
     *
     * @var string
     */
    protected string $migrationSource = "";

    /**
     * Use voucher_relations table to find the new voucher.
     */
    public const SOURCE_RELATION = 'voucher_relation';

    /**
     * Use vouchers.identity_address column to find the new voucher.
     */
    public const SOURCE_IDENTITY = 'voucher_identity';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->printHeader("Physical cards migration", 2);
        $this->askMigrationSource();
        $fund = $this->selectFund();

        $this->printHeader("$fund->name selected!");
        $this->printList($this->buildPhysicalCardStats($fund));

        if (!$this->buildPhysicalCardsQuery($fund)->exists()) {
            $this->printText($this->green("\nNo eligible physical cards found!"));
            $this->exit();
        }

        $this->printText();
        $this->askNextAction($fund);
    }

    /**
     * @return array
     */
    protected function askMigrationSourceList(): array
    {
        return [
            '[1] Use vouchers.identity_address column to find the new voucher.',
            '[2] Use voucher_relations table to find the new voucher.',
            '[3] Exit',
        ];
    }

    /**
     * @return void
     */
    protected function askMigrationSource(): void
    {
        $this->printHeader("Select next action:");
        $this->printList($this->askMigrationSourceList());

        switch ($this->ask("Please select next step:", 1)) {
            case 1: $this->migrationSource = static::SOURCE_IDENTITY; break;
            case 2: $this->migrationSource = static::SOURCE_RELATION; break;
            case 3: $this->exit(); break;
            default: $this->printText("Invalid input!\nPlease try again:\n");
        }

        if (!$this->migrationSource) {
            $this->askMigrationSource();
        }
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function askNextActionList(Fund $fund): array
    {
        $countEligible = $this->buildEligiblePhysicalCardsQuery($fund)->count();
        $countEdgeCases = $this->buildEdgeCasePhysicalCardsQuery($fund)->count();
        $countTotal = $this->buildPhysicalCardsQuery($fund)->count();

        return [
            sprintf('[1] Migrate physical cards [%s item(s)]', $countEligible),
            sprintf('[2] View eligible cards [%s item(s)]', $countEligible),
            sprintf('[3] View edge cases cards [%s item(s)]', $countEdgeCases),
            sprintf('[4] View edge cases cards with details (slower) [%s item(s)]', $countEdgeCases),
            sprintf('[5] View all cards [%s item(s)]', $countTotal),
            '[6] Exit',
        ];
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function askNextAction(Fund $fund): void
    {
        $this->printHeader("Select next action:");
        $this->printList($this->askNextActionList($fund));
        $this->printText();

        switch ($this->ask("Please select next step:", 2)) {
            case 1: $this->migratePhysicalCards($fund); break;
            case 2: $this->printCards($this->buildEligiblePhysicalCardsQuery($fund)->get()); break;
            case 3: $this->printCards($this->buildEdgeCasePhysicalCardsQuery($fund)->get()); break;
            case 4: $this->printCards($this->buildEdgeCasePhysicalCardsQuery($fund)->get(), true); break;
            case 5: $this->printCards($this->buildPhysicalCardsQuery($fund)->get()); break;
            case 6: $this->exit(); break;
            default: $this->printText("Invalid input!\nPlease try again:\n"); break;
        }

        $this->askNextAction($fund);
    }

    /**
     * @return Builder|PhysicalCard
     */
    protected function buildPhysicalCardsQuery(Fund $fund): Builder
    {
        $builder = PhysicalCard::where(function(Builder $builder) use ($fund) {
            // Should be assigned
            if ($this->migrationSource == self::SOURCE_IDENTITY) {
                $builder->whereNotNull('identity_address');
            }

            // Should not be assigned
            if ($this->migrationSource == self::SOURCE_RELATION) {
                $builder->whereNull('identity_address');
            }

            // Should be assigned to vouchers
            $builder->whereHas('voucher', function(Builder $builder) use ($fund) {
                // Which is expired
                VoucherQuery::whereExpiredButActive($builder);

                // And belong to selected fund
                $builder->where('fund_id', $fund->id);

                // Should not have parent id
                $builder->whereNull('parent_id');

                if ($this->migrationSource == self::SOURCE_IDENTITY) {
                    // Should be assigned
                    $builder->whereNotNull('identity_address');
                }

                if ($this->migrationSource == self::SOURCE_RELATION) {
                    // Should not be assigned
                    $builder->whereNull('identity_address');
                    $builder->whereHas('voucher_relation');
                }
            });
        });

        $builder = $builder->addSelect([
            'relation_bsn' => $this->physicalCardRelationSubQuery(),
        ]);

        return PhysicalCard::query()->fromSub($builder, 'physical_cards');
    }

    /**
     * @return VoucherRelation|Builder|\Illuminate\Database\Query\Builder
     */
    protected function physicalCardRelationSubQuery()
    {
        return VoucherRelation::whereHas('voucher', function(Builder $builder) {
            $builder->whereColumn('voucher_relations.voucher_id', 'physical_cards.voucher_id');
        })->select('bsn');
    }

    /**
     * @return Builder|PhysicalCard
     */
    protected function buildEligiblePhysicalCardsQuery(Fund $fund): Builder
    {
        return $this->buildPhysicalCardsQuery($fund)->where(function(Builder $builder) use ($fund) {
            // Should have one active voucher from the same fund as the expired one
            $builder->whereHas('voucher', function(Builder $builder) {
                VoucherQuery::whereExpiredButActive($builder);

                // Should have a fund
                $builder->whereHas('fund', function(Builder $builder) {
                    // Which is active
                    FundQuery::whereActiveFilter($builder);

                    // And should have one voucher
                    $builder->whereHas('vouchers', function(Builder $builder) {
                        // Which is not expired and active
                        VoucherQuery::whereNotExpiredAndActive($builder);

                        // And should not have other physical cards attached
                        $builder->whereDoesntHave('physical_cards');

                        // Should not have parent id
                        $builder->whereNull('parent_id');

                        if ($this->migrationSource == self::SOURCE_IDENTITY) {
                            // That belongs to the same user as the physical card
                            $builder->whereColumn('vouchers.identity_address', 'physical_cards.identity_address');
                        }

                        if ($this->migrationSource == self::SOURCE_RELATION) {
                            // Should not be assigned
                            $builder->whereNull('identity_address');

                            // And relation bsn should match
                            $builder->whereHas('voucher_relation', function(Builder $builder) {
                                $builder->whereColumn('voucher_relations.bsn', 'relation_bsn');
                            });
                        }
                    }, '=');
                });
            });

            if ($this->migrationSource == self::SOURCE_IDENTITY) {
                // Should not have multiple physical cards for the same fund
                $builder->whereHas('identity', function (Builder $builder) use ($fund) {
                    $builder->whereHas('physical_cards', function (Builder $builder) use ($fund) {
                        $builder->whereColumn('physical_cards.identity_address', 'identity_address');

                        $builder->whereHas('voucher', function (Builder $builder) use ($fund) {
                            $builder->whereNull('parent_id');
                            $builder->where('fund_id', $fund->id);
                        });
                    }, '=');
                });
            }

            if ($this->migrationSource == self::SOURCE_RELATION) {
                $builder->whereDoesntHave('identity');
            }
        });
    }

    /**
     * @param Fund $fund
     * @return Builder
     */
    protected function buildEdgeCasePhysicalCardsQuery(Fund $fund): Builder
    {
        $excludeQuery = $this->buildEligiblePhysicalCardsQuery($fund);

        return $this->buildPhysicalCardsQuery($fund)->whereNotIn('id', $excludeQuery->select('id'));
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function buildPhysicalCardStats(Fund $fund): array
    {
        return [
            $this->buildEligiblePhysicalCardsQuery($fund)->count() . " eligible card(s)!",
            $this->buildEdgeCasePhysicalCardsQuery($fund)->count() . " edge case(s)!",
            $this->buildPhysicalCardsQuery($fund)->count() . " total!",
        ];
    }

    /**
     * @return Fund[]|Collection
     */
    protected function printFundsList(): Collection
    {
        $funds = Fund::whereHas('fund_config', function(Builder $builder) {
            $builder->where('allow_physical_cards', 1);
        })->get();

        $this->printList($funds->map(function(Fund $fund) {
            return "[$fund->id] $fund->name (" . ($fund->organization->name ?? '') . ")";
        })->toArray());
        $this->printText();

        return $funds;
    }

    /**
     * @return Fund
     */
    private function selectFund(): Fund
    {
        $this->printHeader("Please select the fund");
        $funds = $this->printFundsList();

        if ($funds->count() == 0) {
            $this->printText($this->red("\nNo funds where physical cards are available found!"));
            $this->exit();
        }

        $fundId = $this->ask('Select fund id: ', $funds[0]->id);
        $fundModel = $funds->where('id', '=', $fundId)->first();

        if ($fundModel) {
            return $fundModel;
        }

        $this->printText("Invalid fund selected!\nPlease try again!\n");
        return $this->selectFund();
    }

    /**
     * @param Fund $fund
     * @return void
     */
    private function migratePhysicalCards(Fund $fund): void
    {
        $cards = $this->buildEligiblePhysicalCardsQuery($fund)->get();
        $failedCards = new Collection();

        foreach ($cards as $index => $card) {
            $this->printText($this->green(sprintf("- migrating %s of %s", $index + 1, $cards->count())));

            if ($errorMessage = $this->migratePhysicalCard($card, $fund)) {
                $failedCards->push($card);

                $this->printText($this->yellow(sprintf(
                    "[Warning!] Edge case detected on card: [$card->id] (%s).",
                    $errorMessage['error'] ?? ''
                )));

                if ($errorMessage['vouchers'] ?? null) {
                    $this->printModels($errorMessage['vouchers'], 'id', 'fund_name', 'state');
                }
            }
        }

        if ($failedCards->count() == 0) {
            $this->printText($this->green($cards->count() . " card(s) migrated!\n"));

            if ($cards->count() > 0) {
                $this->printCardsActions($cards);
            }

            $this->printSeparator();
            $this->printText("\n");
            return;
        }

        $this->printText($this->red($failedCards->count() . " card(s) failed to migrate:\n"));
        $this->printCardsActions($failedCards, "Failed cards");
        $this->printText();
    }

    /**
     * @param PhysicalCard $card
     * @param Fund $fund
     * @param bool $dryRun
     * @return array|null
     */
    protected function migratePhysicalCard(PhysicalCard $card, Fund $fund, bool $dryRun = false): ?array
    {
        $cardSiblings = $this->getCardSiblings($card, $fund);
        $vouchers = $this->getCardReplacementVouchers($card, $fund);
        $error = null;

        if ($cardSiblings->count() == 0 && $vouchers->count() == 1) {
            if (!$dryRun) {
                $preValue = $card->voucher_id;

                $card->updateModel([
                    'voucher_id' => $vouchers[0]->id,
                ])->log($card::EVENT_MIGRATED, [
                    'physical_card' => $card,
                ], [
                    'prev_voucher_id' =>  $preValue,
                ]);

                $this->printText($this->green(sprintf(
                    "- migrated physical_card: [%s] voucher_id [%s]\n",
                    $card->id,
                    "$preValue => $card->voucher_id"
                )));
            }
            return null;
        }

        if ($cardSiblings->count() > 1) {
            $error = "To many cards on same fund";
        }

        if ($vouchers->count() == 0) {
            $error = "No vouchers for replacement";
        }

        if ($vouchers->count() > 1) {
            $error = "To many vouchers for replacement (" . $vouchers->count() . ")";
        }

        return compact('error', 'vouchers');
    }

    /**
     * @param PhysicalCard $card
     * @param Fund $fund
     * @return Builder|Relation
     */
    protected function getCardSiblings(PhysicalCard $card, Fund $fund)
    {
        switch ($this->migrationSource) {
            case self::SOURCE_IDENTITY: $querySiblings = $card->identity->physical_cards(); break;
            case self::SOURCE_RELATION: {
                $querySiblings = PhysicalCard::whereHas('voucher', function(Builder $builder) use ($card) {
                    $builder->whereHas('voucher_relation', function(Builder $builder) use ($card) {
                        $builder->where('bsn', '==', $card->voucher->voucher_relation->bsn);
                    });
                });
            } break;
            default: exit();
        }

        if ($this->migrationSource == static::SOURCE_IDENTITY) {
            $querySiblings = $card->identity->physical_cards();
        }

        $querySiblings->where('id', '!=', $card->id);

        return $querySiblings->whereHas('voucher', function(Builder $builder) use ($fund) {
            $builder->whereNull('parent_id');
            $builder->where('fund_id', $fund->id);
        });
    }

    /**
     * @param PhysicalCard $card
     * @param Fund $fund
     * @return Collection|Voucher[]
     */
    protected function getCardReplacementVouchers(PhysicalCard $card, Fund $fund): Collection
    {
        return Voucher::where(function(Builder $builder) use ($card, $fund) {
            // Which is not expired and active
            VoucherQuery::whereNotExpiredAndActive($builder);

            // And should not have other physical cards attached
            $builder->whereDoesntHave('physical_cards');

            if ($this->migrationSource == self::SOURCE_IDENTITY) {
                // That belongs to the same user as the physical card
                $builder->where('identity_address', $card->identity_address);
            }

            if ($this->migrationSource == self::SOURCE_RELATION) {
                // Should not be assigned
                $builder->whereNull('identity_address');

                // And relation bsn should match
                $builder->whereHas('voucher_relation', function(Builder $builder) use ($card) {
                    $builder->where('bsn', $card->voucher->voucher_relation->bsn);
                });
            }
        })->where([
            'fund_id' => $fund->id
        ])->whereNull('parent_id')->get();
    }

    /**
     * @param Collection $cards
     * @param string|null $header
     * @return void
     */
    protected function printCardsActions(Collection $cards, ?string $header = null)
    {
        $this->printHeader("Card(s) actions:");
        $this->printList([
            '[1] Show cards',
            '[2] Continue',
        ]);
        $this->printText();

        switch ($this->ask("Please select next step:", 2)) {
            case 1: $header ? $this->printCards($cards, $header) : $this->printCards($cards); break;
            case 2: return;
            default: $this->printText("Invalid input!\nPlease try again:\n");
        }
    }

    /**
     * @param Collection|PhysicalCard[] $cards
     * @param string $header
     * @param bool $validate
     * @return void
     */
    protected function printCards(
        Collection $cards,
        bool $validate = false,
        string $header = "List physical cards:"
    ) {
        $headers = array_merge([
            'id', 'code', 'fund_id', 'voucher_id'
        ], $validate ? ['validation'] : []);

        $body = $cards->map(function(PhysicalCard $card) use ($validate) {
            return array_merge([
                $card->id,
                $card->code,
                $card->voucher->fund->name,
                $card->voucher_id,
            ], $validate ? [
                $this->migratePhysicalCard($card, $card->voucher->fund, true)['error'] ?? '-'
            ] : []);
        })->toArray();

        $this->printHeader($header);
        $this->table($headers, $body);
        $this->printSeparator();
        $this->printText();
    }
}
