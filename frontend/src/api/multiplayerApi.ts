/**
 * multiplayerApi.ts
 * -----------------
 * Handles API calls for multiplayer matchmaking.
 * (join queue + check session status)
 */

const BASE_URL = "http://localhost:8888/ludo-game/backend/api/game/multiplayer/";

/**
 * Join matchmaking queue (create or join session)
 */
export async function joinQueue() {
  try {
    const res = await fetch(`${BASE_URL}queue.php`, {
      method: "POST",
      credentials: "include"
    });
    return await res.json();
  } catch (err) {
    console.error("❌ joinQueue failed:", err);
    return { success: false, message: "Network error." };
  }
}

/**
 * Poll session status
 */
export async function checkStatus(sessionId: number) {
  try {
    const res = await fetch(`${BASE_URL}status.php?sessionId=${sessionId}`, {
      method: "GET",
      credentials: "include"
    });
    return await res.json();
  } catch (err) {
    console.error("❌ checkStatus failed:", err);
    return { success: false, message: "Network error." };
  }
}
