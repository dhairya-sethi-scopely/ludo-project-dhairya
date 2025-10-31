   <?php
   /**
    * Class WinConditionService
   *
     * Checks if a player has won (all 4 tokens reached HOME).
     */
    class WinConditionService {

        /**
         * Check if all 4 tokens for a player are in HOME.
         *
         * @param array $tokens Array of token data for a player.
         * @return bool True if the player has won.
         */
        public function checkWin(array $tokens): bool {
            // Ensure exactly 4 tokens are considered
            if (count($tokens) < 4) {
                return false;
            }

            $homeCount = 0;

            foreach ($tokens as $token) {
                $position = $token['position'] ?? '';
                $steps = (int)($token['steps'] ?? 0);

                // Count as HOME only if both position is HOME AND steps == 57
                if ($position === "HOME" && $steps === 57) {
                    $homeCount++;
                }
            }

            if ($homeCount === 4) {
                return true;
            }

            return false;
        }
    }
