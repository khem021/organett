<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Money and weight arithmetic done on decimal strings.
 *
 * Quantities, prices and amounts are decimal(10,2) columns. Multiplying them as
 * floats drifts (0.03 kg at 5.50 is 0.16499999999999998), so totals are worked out
 * with bcmath and rounded half up to the cent, and every input that feeds them is
 * held to what the column can store.
 */
final class Money
{
    /** The largest value a decimal(10,2) column holds. */
    public const MAX = '99999999.99';

    /**
     * Validation rules for a two-decimal quantity, price or amount.
     *
     * @return array<int, string>
     */
    public static function rules(string $min = '0.01'): array
    {
        return ['required', 'numeric', 'min:'.$min, 'max:'.self::MAX, 'decimal:0,2'];
    }

    /** quantity x price, rounded half up to two decimals, as a string such as "17.50". */
    public static function total(string|int|float $quantity, string|int|float $price): string
    {
        return bcadd(bcmul((string) $quantity, (string) $price, 4), '0.005', 2);
    }

    /** True when a value (as a decimal string) is more than a decimal(10,2) column can hold. */
    public static function exceeds(string $value): bool
    {
        return bccomp($value, self::MAX, 2) > 0;
    }

    /**
     * @throws ValidationException when the value would not fit in the column
     */
    public static function assertFits(string $value, string $field, string $what): void
    {
        if (self::exceeds($value)) {
            throw ValidationException::withMessages([
                $field => "{$what} is too large: the most that can be recorded is ".number_format((float) self::MAX, 2).'.',
            ]);
        }
    }
}
