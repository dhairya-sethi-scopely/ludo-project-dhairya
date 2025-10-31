<?php
/**
 * Class TurnManager
 *
 * Controls turn rotation between players.
 */
class TurnManager {
    public function getNextTurn(int $currentTurn, int $diceValue, array $players): int {
        error_log("Calculating next turn. Current: $currentTurn, Dice: $diceValue, Players: " . implode(',', $players));
        // Player gets another turn if they roll a 6
        if ($diceValue === 6) {
            return $currentTurn;
        }

        // Otherwise, go to next player
        $nextTurn = ($currentTurn + 1) % count($players);
        return $nextTurn;
    }
}

