<?php

namespace Tests\Unit;

use App\Models\FundFormulaCondition;
use App\Models\RecordType;
use Tests\TestCase;

class FundFormulaConditionTest extends TestCase
{
    /**
     * @return void
     */
    public function testMatchesDateComparisonOperators(): void
    {
        $operators = [
            '=' => [false, true, false],
            '<' => [true, false, false],
            '<=' => [true, true, false],
            '>' => [false, false, true],
            '>=' => [false, true, true],
        ];

        foreach (['2000-03-01', '01-03-2000'] as $comparison) {
            foreach ($operators as $operator => $expected) {
                $condition = $this->makeCondition(RecordType::TYPE_DATE, $operator, $comparison);

                foreach ([
                    ['2000-02-29', '2000-03-01', '2000-03-02'],
                    ['29-02-2000', '01-03-2000', '02-03-2000'],
                ] as $values) {
                    foreach ($values as $index => $value) {
                        $this->assertSame(
                            $expected[$index],
                            $condition->matchesValue($value),
                            "$value $operator $comparison",
                        );
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function testMatchesNumericComparisonOperators(): void
    {
        foreach ([RecordType::TYPE_NUMBER, RecordType::TYPE_SELECT_NUMBER] as $type) {
            foreach ([
                '=' => [false, true, false],
                '<' => [true, false, false],
                '<=' => [true, true, false],
                '>' => [false, false, true],
                '>=' => [false, true, true],
            ] as $operator => $expected) {
                foreach ([
                    'integer strings' => ['10', ['2', '10', '11']],
                    'decimal strings' => ['10.5', ['10.25', '10.50', '10.75']],
                    'numeric inputs' => ['10', [2, 10, 10.5]],
                ] as $variant => [$comparison, $values]) {
                    $condition = $this->makeCondition($type, $operator, $comparison);

                    foreach ($values as $index => $value) {
                        $this->assertSame(
                            $expected[$index],
                            $condition->matchesValue($value),
                            "$type $variant: $value $operator $comparison",
                        );
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function testMissingInvalidOrUnsupportedConditionValuesDoNotMatch(): void
    {
        foreach ([
            'missing date' => [RecordType::TYPE_DATE, '=', '2000-01-01', null],
            'empty date' => [RecordType::TYPE_DATE, '>', '2000-01-01', ''],
            'invalid calendar date' => [RecordType::TYPE_DATE, '>', '2000-01-01', '2001-02-29'],
            'invalid comparison date' => [RecordType::TYPE_DATE, '<', '2001-02-29', '2000-01-01'],
            'date with time' => [RecordType::TYPE_DATE, '=', '2000-01-01', '2000-01-01 00:00:00'],
            'non-string date' => [RecordType::TYPE_DATE, '>', '2000-01-01', 20000102],
            'missing number' => [RecordType::TYPE_NUMBER, '<', '10', null],
            'invalid number' => [RecordType::TYPE_NUMBER, '<', '10', 'abc'],
            'invalid comparison number' => [RecordType::TYPE_NUMBER, '=', 'abc', 'abc'],
            'unsupported string type' => [RecordType::TYPE_STRING, '=', 'same', 'same'],
            'unsupported operator' => [RecordType::TYPE_NUMBER, '!=', '10', '2'],
        ] as $variant => [$type, $operator, $comparison, $value]) {
            $this->assertFalse($this->makeCondition($type, $operator, $comparison)->matchesValue($value), $variant);
        }
    }

    /**
     * @param string $type
     * @param string $operator
     * @param string $value
     * @return FundFormulaCondition
     */
    private function makeCondition(string $type, string $operator, string $value): FundFormulaCondition
    {
        $condition = new FundFormulaCondition(compact('operator', 'value'));
        $condition->setRelation('record_type', new RecordType(['type' => $type]));

        return $condition;
    }
}
