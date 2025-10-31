<?php
/**
 * RandomNumberGenerator
 * ----------------------
 * Provides secure random number generation between a configurable range.
 * Lightweight utility used for dice rolls, AI randomness, and other game logic.
 */

class RandomNumberGenerator
{
    /**
     * Generates a secure random number between the given range.
     * Automatically swaps values if min > max.
     *
     * @param int $min Minimum range value (default = 1)
     * @param int $max Maximum range value (default = 6)
     * @return int Random integer between $min and $max
     */
    public static function between(int $min = 1, int $max = 6): int
    {
        // return 3;
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        return random_int($min, $max);
    }
}
