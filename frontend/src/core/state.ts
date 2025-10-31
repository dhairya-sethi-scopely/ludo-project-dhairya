/**
 * state.ts
 * ---------
 * Manages game state locally in the browser.
 * Stores data like session ID, player ID, and mode (PvP or vs AI).
 */

import { GameState } from "./types.js";

// In-memory cache
let gameState: GameState | null = null;

/**
 * ✅ Saves the game state (both in memory and localStorage)
 */
export function setGameState(state: GameState): void {
  gameState = state;
  localStorage.setItem("gameState", JSON.stringify(state));
  console.log("💾 Game state saved:", state);
}

/**
 * ✅ Loads the game state from memory or localStorage.
 * Ensures persistence after page reloads or redirects.
 * Redirects safely to menu.html if missing.
 */
export function getGameState(): GameState {
  if (!gameState) {
    const saved = localStorage.getItem("gameState");

    if (saved) {
      try {
        gameState = JSON.parse(saved);
      } catch (err) {
        console.error("⚠️ Failed to parse saved game state:", err);
        localStorage.removeItem("gameState");
      }
    }
  }

  if (!gameState) {
    console.warn("⚠️ No game state found. Redirecting to menu...");

    // 🟢 FIXED LINE BELOW:
    // old → window.location.href = "/ludo-game/frontend//menu.html";
    // new → single slash + correct /public/ path
    window.location.href = "/ludo-game/frontend/menu.html";

    throw new Error("Game state not found — redirected to menu.");
  }

  return gameState;
}

/**
 * Clears the stored game state (used when match expires or on logout)
 */
export function clearGameState(): void {
  localStorage.removeItem("gameState");
  gameState = null;
  console.log("🧹 Game state cleared.");
}
