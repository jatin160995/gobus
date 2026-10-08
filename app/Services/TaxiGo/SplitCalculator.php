<?php

namespace App\Services\TaxiGo;

use InvalidArgumentException;

/**
 * Splits a fare between beneficiaries by percentage, in whole XAF.
 * Each share is rounded down; the francs left over go to the largest share,
 * so the shares always add up to exactly the fare.
 */
class SplitCalculator
{
    /**
     * @param  array<string, float|int|string>  $percentages  key => percent, must total 100
     * @return array<string, int>                              key => amount
     */
    public function split(int $total, array $percentages): array
    {
        if ($total < 0) {
            throw new InvalidArgumentException('The fare cannot be negative.');
        }

        $sum = array_sum(array_map('floatval', $percentages));
        if (abs($sum - 100) > 0.001) {
            throw new InvalidArgumentException("Split percentages must total 100, got {$sum}.");
        }

        $amounts = [];
        foreach ($percentages as $key => $percent) {
            $amounts[$key] = intdiv($total * (int) round((float) $percent * 100), 10000);
        }

        $largest = array_keys($percentages, max($percentages))[0];
        $amounts[$largest] += $total - array_sum($amounts);

        return $amounts;
    }
}
