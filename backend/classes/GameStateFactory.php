<?php
class GameStateFactory {
    private array $config;
    private array $defaultState;

    public function __construct(array $config, array $defaultState) {
        $this->config = $config;
        $this->defaultState = $defaultState;
    }

    public function createInitialState(array $players): array {
        $state = $this->defaultState;
        $tokensPerPlayer = $this->config['TOKENS_PER_PLAYER'] ?? 4;

        foreach ($players as $index => $playerId) {
            // Assign simple colour index for predictable IDs: first player = P1_, second = P2_
            $pidPrefix = 'P' . ($index + 1);

            $state['tokens'][$playerId] = [];
            for ($i = 1; $i <= $tokensPerPlayer; $i++) {
                $state['tokens'][$playerId][] = [
                    'id' => "{$pidPrefix}_T{$i}",
                    'position' => 'YARD',
                    'steps' => 0,
                ];
            }

            $state['captures'][$playerId] = 0;
            $state['move_counters'][$playerId] = 0;
        }

        $state['lastDice'] = null;
        return $state;
    }

    /** ✅ Setter for AI metadata */
    public function addAIMetadata(array $aiMeta): void {
        $this->defaultState['ai_meta'] = $aiMeta;
    }

    /** ✅ Getter for reference */
    public function getDefaultState(): array {
        return $this->defaultState;
    }
}
