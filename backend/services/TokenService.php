<?php
/**
 * TokenService
 * ------------
 * Handles all token movement and capture logic for PvP mode.
 */

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

class TokenService
{
    /**
     * Move a token forward based on dice value.
     * - From YARD → PATH only on dice = 6
     * - Moves along PATH but cannot exceed step 57 (HOME)
     * - Marks token HOME when steps reach 57
     */
    public function moveToken(array &$token, int $diceValue): void
    {
        // From YARD → PATH (only if dice == 6)
        if ($token['position'] === 'YARD') {
            if ($diceValue === 6) {
                $token['position'] = 'PATH';
                $token['steps'] = 1;
            }
            return;
        }

        // On PATH
        if ($token['position'] === 'PATH') {
            $newSteps = $token['steps'] + $diceValue;

            // Cannot exceed final step
            if ($newSteps > 57) return;

            // Reached HOME
            if ($newSteps === 57) {
                $token['position'] = 'HOME';
                $token['steps'] = 57;
                return;
            }

            // Normal movement
            $token['steps'] = $newSteps;
        }
    }

    /**
     * Check if a capture occurred (landing on opponent’s position).
     *
     * @param array $movingToken  The token that just moved
     * @param array &$opponentTokens  Opponent’s tokens (by reference)
     * @return bool  True if a capture occurred
     */
    public function checkCapture(array $movingToken, array &$opponentTokens): bool
    {
        $captured = false;
        $movingX = $movingToken['x'] ?? null;
        $movingY = $movingToken['y'] ?? null;
        if (!$movingX || !$movingY) return false;

        foreach ($opponentTokens as &$token) {
            if ($token['position'] !== 'PATH') continue;

            $path = str_starts_with($token['id'], 'P1_')
                ? $this->getRedPath()
                : $this->getBluePath();

            $step = (int) $token['steps'];
            $oppCoord = $path[$step - 1] ?? null;
            if (!$oppCoord) continue;

            if ($oppCoord['x'] === $movingX && $oppCoord['y'] === $movingY) {
                $this->resetTokenToYard($token);
                $captured = true;
            }
        }

        return $captured;
    }

    /**
     * Reset a captured token back to the YARD.
     */
    public function resetTokenToYard(array &$token): void
    {
        $token['position'] = 'YARD';
        $token['steps'] = 0;
    }

    /** Return red player's board path (matches frontend path). */
    public function getRedPath(): array
    {
        return [
            ['x'=>7,'y'=>2], ['x'=>7,'y'=>3], ['x'=>7,'y'=>4], ['x'=>7,'y'=>5], ['x'=>7,'y'=>6],
            ['x'=>6,'y'=>7], ['x'=>5,'y'=>7], ['x'=>4,'y'=>7], ['x'=>3,'y'=>7], ['x'=>2,'y'=>7],
            ['x'=>1,'y'=>7], ['x'=>1,'y'=>8], ['x'=>1,'y'=>9], ['x'=>2,'y'=>9], ['x'=>3,'y'=>9],
            ['x'=>4,'y'=>9], ['x'=>5,'y'=>9], ['x'=>6,'y'=>9], ['x'=>7,'y'=>10], ['x'=>7,'y'=>11],
            ['x'=>7,'y'=>12], ['x'=>7,'y'=>13], ['x'=>7,'y'=>14], ['x'=>7,'y'=>15], ['x'=>8,'y'=>15],
            ['x'=>9,'y'=>15], ['x'=>9,'y'=>14], ['x'=>9,'y'=>13], ['x'=>9,'y'=>12], ['x'=>9,'y'=>11],
            ['x'=>9,'y'=>10], ['x'=>10,'y'=>9], ['x'=>11,'y'=>9], ['x'=>12,'y'=>9], ['x'=>13,'y'=>9],
            ['x'=>14,'y'=>9], ['x'=>15,'y'=>9], ['x'=>15,'y'=>8], ['x'=>15,'y'=>7], ['x'=>14,'y'=>7],
            ['x'=>13,'y'=>7], ['x'=>12,'y'=>7], ['x'=>11,'y'=>7], ['x'=>10,'y'=>7], ['x'=>9,'y'=>6],
            ['x'=>9,'y'=>5], ['x'=>9,'y'=>4], ['x'=>9,'y'=>3], ['x'=>9,'y'=>2], ['x'=>9,'y'=>1],
            ['x'=>8,'y'=>1], ['x'=>8,'y'=>2], ['x'=>8,'y'=>3], ['x'=>8,'y'=>4], ['x'=>8,'y'=>5], ['x'=>8,'y'=>6]
        ];
    }

    /** Return blue player's board path (matches frontend path). */
    public function getBluePath(): array
    {
        return [
            ['x'=>14,'y'=>7], ['x'=>13,'y'=>7], ['x'=>12,'y'=>7], ['x'=>11,'y'=>7], ['x'=>10,'y'=>7],
            ['x'=>9,'y'=>6], ['x'=>9,'y'=>5], ['x'=>9,'y'=>4], ['x'=>9,'y'=>3], ['x'=>9,'y'=>2],
            ['x'=>9,'y'=>1], ['x'=>8,'y'=>1], ['x'=>7,'y'=>1], ['x'=>7,'y'=>2], ['x'=>7,'y'=>3],
            ['x'=>7,'y'=>4], ['x'=>7,'y'=>5], ['x'=>7,'y'=>6], ['x'=>6,'y'=>7], ['x'=>5,'y'=>7],
            ['x'=>4,'y'=>7], ['x'=>3,'y'=>7], ['x'=>2,'y'=>7], ['x'=>1,'y'=>7], ['x'=>1,'y'=>8],
            ['x'=>1,'y'=>9], ['x'=>2,'y'=>9], ['x'=>3,'y'=>9], ['x'=>4,'y'=>9], ['x'=>5,'y'=>9],
            ['x'=>6,'y'=>9], ['x'=>7,'y'=>10], ['x'=>7,'y'=>11], ['x'=>7,'y'=>12], ['x'=>7,'y'=>13],
            ['x'=>7,'y'=>14], ['x'=>7,'y'=>15], ['x'=>8,'y'=>15], ['x'=>9,'y'=>15], ['x'=>9,'y'=>14],
            ['x'=>9,'y'=>13], ['x'=>9,'y'=>12], ['x'=>9,'y'=>11], ['x'=>9,'y'=>10], ['x'=>10,'y'=>9],
            ['x'=>11,'y'=>9], ['x'=>12,'y'=>9], ['x'=>13,'y'=>9], ['x'=>14,'y'=>9], ['x'=>15,'y'=>9],
            ['x'=>15,'y'=>8], ['x'=>14,'y'=>8], ['x'=>13,'y'=>8], ['x'=>12,'y'=>8], ['x'=>11,'y'=>8],
            ['x'=>10,'y'=>8], ['x'=>9,'y'=>8]
        ];
    }
}
