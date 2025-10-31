/**
 * Creates 4 tokens for a player and places them in yard.
 */
export function createPlayerTokens(player) {
    return Array.from({ length: 4 }, (_, i) => ({
        id: `${player[0].toUpperCase()}${i + 1}`,
        player,
        position: null,
        steps: 0,
        status: "yard"
    }));
}
