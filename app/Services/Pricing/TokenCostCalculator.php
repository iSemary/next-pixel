<?php

namespace App\Services\Pricing;

class TokenCostCalculator
{
    public const SCALE = 9;

    /**
     * Calculate exact USD cost for a token category at 9-decimal precision.
     * Formula: (tokens * ratePer1M) / 1,000,000
     */
    public static function categoryCost(int $tokens, string $ratePer1M): string
    {
        if ($tokens <= 0 || bccomp($ratePer1M, '0', self::SCALE) <= 0) {
            return '0.000000000';
        }

        $numerator = bcmul((string) $tokens, $ratePer1M, self::SCALE);

        return bcdiv($numerator, '1000000', self::SCALE);
    }

    /**
     * Sum multiple USD cost strings with exact 9-decimal precision.
     */
    public static function sum(string ...$costs): string
    {
        $total = '0.000000000';

        foreach ($costs as $cost) {
            $total = bcadd($total, $cost, self::SCALE);
        }

        return $total;
    }

    /**
     * Apply the platform markup rate to a provider cost.
     *
     * @return array{markup_amount: string, user_cost: string}
     */
    public static function applyMarkup(string $providerCost, string $markupRate): array
    {
        $markupAmount = bcmul($providerCost, $markupRate, self::SCALE);
        $userCost = bcadd($providerCost, $markupAmount, self::SCALE);

        return [
            'markup_amount' => $markupAmount,
            'user_cost' => $userCost,
        ];
    }
}
