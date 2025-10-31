/**
 * waitingUI.ts
 * -------------
 * Polls multiplayer session status from backend.
 * Updates countdown and waiting room messages.
 */

import { checkStatus } from "../api/multiplayerApi.js";
import { getGameState } from "../core/state.js";

const state = getGameState();
const sessionId = state.sessionId;

const waitingText = document.getElementById("waitingText")!;
const timerElem = document.getElementById("timeRemaining")!;
const statusMsg = document.getElementById("statusMessage")!;
const cancelBtn = document.getElementById("cancelBtn")!;

let pollInterval: number | null = null;

/** Update waiting room UI */
function updateUI(data: any) {
  if (data.isActive) {
    waitingText.textContent = "Match found! Starting game...";
    statusMsg.textContent = "Redirecting to game screen...";
    setTimeout(() => {
      window.location.href = "/ludo-game/frontend/public/gamePVP.html";
    }, 1500);
  } else {
    waitingText.textContent = `Waiting for opponent... (${data.currentCount}/2 joined)`;
    const safeTime = Math.min(90, Math.max(0, data.timeRemaining)); // clamp 0–90
    timerElem.textContent = `${safeTime}s`;
  }
}

/** Polls backend every 2 seconds for matchmaking status */
async function pollStatus() {
  const res = await checkStatus(sessionId);

  if (res.id === "ERR_ID_923") {
    clearInterval(pollInterval!);
    waitingText.textContent = "Session expired.";
    statusMsg.textContent = "No player joined in time. Returning to menu...";
    setTimeout(() => {
      window.location.href = "/ludo-game/frontend/menu.html";
    }, 3000);
    return;
  }

  if (res.id === "ERR_ID_917" || res.id === "ERR_ID_900") {
    clearInterval(pollInterval!);
    waitingText.textContent = "Session not found or unauthorized.";
    statusMsg.textContent = "Please log in again.";
    return;
  }

  if (res.success && res.data) {
    updateUI(res.data);
  } else {
    statusMsg.textContent = res.message || "Server not responding...";
  }
}

// Start polling every 2 seconds
pollInterval = setInterval(pollStatus, 2000);

// Allow cancel
cancelBtn.addEventListener("click", () => {
  clearInterval(pollInterval!);
  window.location.href = "/ludo-game/frontend/menu.html";
});
