/**
 * core/types.ts
 * --------------
 * Shared TypeScript interfaces between frontend logic and backend APIs.
 */

/* ===============================
   🔹 Generic API Response
   =============================== */
export interface ApiResponse {
  success?: boolean;
  message_id?: string;
  message?: string;
  error_id?: string;
  error_code?: number;
  [key: string]: any;
}

/* ===============================
   🏁 init-game.php
   =============================== */
export interface InitSessionResponse extends ApiResponse {
  session_id?: number;
  mode?: "pvp" | "vs_ai";
}

/* ===============================
   🎲 roll-dice.php
   =============================== */
export interface DiceResponse extends ApiResponse {
  dice_value?: number;
  next_turn?: string | { id?: number; role?: "human" | "ai" };
  next_turn_role?: "human" | "ai";
  valid_tokens?: string[];
}

/* ===============================
   🎯 move-token.php
   =============================== */
export interface MoveResponse extends ApiResponse {
  token_id?: string;
  new_position?: PositionData | string;
  next_turn?: string | { id?: number; role?: "human" | "ai" };
  next_turn_role?: "human" | "ai";
}

export interface PositionData {
  x?: number;
  y?: number;
  steps?: number;
}

/* ===============================
   🧩 Multiplayer: status.php
   =============================== */
export interface MultiplayerStatusResponse extends ApiResponse {
  data?: {
    participants?: number[];
    currentCount?: number;
    maxPlayers?: number;
    timeRemaining?: number;
    isActive?: boolean;
  };
}

/* ===============================
   🧠 Client-Side Game State
   =============================== */
export interface GameState {
  sessionId: number;
  playerId?: number;
  mode: "pvp" | "vs_ai";
  isActive?: boolean;             // ✅ for PvP sessions
  participants?: number[];        // ✅ who joined
}

/* ===============================
   🎯 Token (frontend representation)
   =============================== */
export interface Token {
  id: string;        // e.g., P1_T1
  player: string;    // e.g., P1 or P2
  x: number;
  y: number;
  steps: number;
}
