import { Node } from "./boardPath";

export type TokenStatus = "yard" | "path" | "home";

export interface Token {
  id: string;             // e.g., "R1", "R2", "B1"
  player: string;         // "red", "blue", "green", "yellow"
  position: Node | null;  // Current node on path, null if in yard
  steps: number;          // Number of steps moved
  status: TokenStatus;    // Current state
}

/**
 * Creates 4 tokens for a player and places them in yard.
 */
export function createPlayerTokens(player: string): Token[] {
  return Array.from({ length: 4 }, (_, i) => ({
    id: `${player[0].toUpperCase()}${i + 1}`,
    player,
    position: null,
    steps: 0,
    status: "yard" as TokenStatus
  }));
}
