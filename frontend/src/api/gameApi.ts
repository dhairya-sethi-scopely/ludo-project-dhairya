/**
 * gameApi.ts
 * -----------
 * Contains helper functions to call backend PHP APIs.
 * Each function returns a Promise that resolves to a typed response.
 */

import {
  InitSessionResponse,
  DiceResponse,
  MoveResponse,
  ApiResponse,
} from "../core/types";

// Base URL for backend endpoints (adjust if your backend runs on a different port)
const BASE_URL = "http://localhost:8888/ludo-game/backend/api/game/";

/**
 * Starts a new game session.
 */
export async function initGame(mode: "pvp" | "vs_ai"): Promise<InitSessionResponse> {
  const body = {
    host_id: 1,
    players: [1],
    game_prize: 100,
    mode,
  };

  const res = await fetch(`${BASE_URL}init-game.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include", // ensures cookies are sent
    body: JSON.stringify(body),
  });

  return res.json();
}

/**
 * Rolls a dice for the current player.
 */
export async function rollDice(sessionId: number): Promise<DiceResponse> {
  const res = await fetch(`${BASE_URL}roll-dice.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ session_id: sessionId }),
  });

  return res.json();
}

/**
 * Moves a player’s token after dice roll.
 */
export async function moveToken(
  sessionId: number,
  tokenId: string,
  diceValue: number
): Promise<MoveResponse> {
  console.log("Moving token:", { sessionId, tokenId, diceValue });

  const res = await fetch(`${BASE_URL}move-token.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({
      session_id: sessionId,
      token_id: tokenId,
      dice_value: diceValue,
    }),
  });

  const data = await res.json();          // read body once
  console.log("Move token response:", data);
  return data;                            // return parsed object
}

/**
 * 🤖 Triggers AI’s automatic move when it's AI's turn.
 * Calls backend/api/game/ai-turn.php
 */
export async function triggerAITurn(sessionId: number): Promise<ApiResponse> {
  const res = await fetch(`${BASE_URL}ai-turn.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ session_id: sessionId }),
  });

  return res.json();
}

/** 🧭 Gets current game session state (whose turn, etc.) */
export async function getSession(sessionId: number): Promise<any> {
  const res = await fetch(`${BASE_URL}get-session.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ session_id: sessionId })
  });
  return res.json();
}
