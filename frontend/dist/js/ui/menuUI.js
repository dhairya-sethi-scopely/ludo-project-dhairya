/**
 * menuUI.ts
 * ----------
 * Handles mode selection (PVP / VS AI) and initializes the game session via backend API.
 */
import { initGame } from "../api/gameApi.js"; // For AI mode
import { joinQueue } from "../api/multiplayerApi.js"; // ✅ For PvP matchmaking
import { setGameState } from "../core/state.js";
export function setupMenu() {
    const btnPVP = document.getElementById("btnPVP");
    const btnAI = document.getElementById("btnAI");
    const status = document.getElementById("menuStatus");
    if (!btnPVP || !btnAI || !status) {
        console.error("❌ Menu UI elements not found on page.");
        return;
    }
    /**
     * === Player vs Player Mode (Multiplayer Matchmaking) ===
     */
    btnPVP.addEventListener("click", async () => {
        try {
            status.textContent = "🎮 Searching for opponent...";
            btnPVP.disabled = true;
            // 🔹 Call backend matchmaking endpoint
            const response = await joinQueue();
            console.log("PVP Queue Response:", response);
            if (response.success && response.data?.sessionId) {
                // ✅ Store session data (so waitingUI can access it)
                setGameState({
                    sessionId: response.data.sessionId,
                    mode: "pvp",
                });
                status.textContent = "✅ Matchmaking started! Waiting for another player...";
                // Redirect to waiting room page
                window.location.href = "/ludo-game/frontend/waiting.html";
            }
            else {
                // ❌ Error from backend
                status.textContent =
                    response.message ||
                        "❌ Failed to start multiplayer matchmaking. Please try again.";
                btnPVP.disabled = false;
            }
        }
        catch (err) {
            console.error("❌ Error during PVP matchmaking:", err);
            status.textContent = "⚠️ Something went wrong while starting multiplayer mode.";
            btnPVP.disabled = false;
        }
    });
    /**
     * === Player vs AI Mode ===
     */
    btnAI.addEventListener("click", async () => {
        try {
            status.textContent = "🎮 Starting Player vs AI game...";
            btnAI.disabled = true;
            const response = await initGame("vs_ai");
            console.log("AI Init Response:", response);
            if (response.success && response.session_id) {
                // ✅ Store session data in global state
                setGameState({
                    sessionId: response.session_id,
                    mode: "vs_ai",
                });
                status.textContent = "✅ AI game started successfully!";
                window.location.href = "/ludo-game/frontend/public/game.html";
            }
            else {
                status.textContent =
                    response.message || "❌ Failed to start AI game.";
                btnAI.disabled = false;
            }
        }
        catch (err) {
            console.error("❌ Error starting AI game:", err);
            status.textContent = "⚠️ Something went wrong while starting the AI game.";
            btnAI.disabled = false;
        }
    });
}
/**
 * menuUI.ts
 * ----------
 * Handles mode selection (PVP / VS AI) and initializes the game session via backend API.
 */
// import { initGame } from "../api/gameApi.js";             // For AI mode
// import { joinQueue } from "../api/multiplayerApi.js";      // ✅ For PvP matchmaking
// import { setGameState } from "../core/state.js";
// export function setupMenu(): void {
//   const btnPVP = document.getElementById("btnPVP") as HTMLButtonElement | null;
//   const btnAI = document.getElementById("btnAI") as HTMLButtonElement | null;
//   const status = document.getElementById("menuStatus") as HTMLElement | null;
//   if (!btnPVP || !btnAI || !status) {
//     console.error("Menu UI elements not found on page.");
//     return;
//   }
//   /**
//    * === Player vs Player Mode (Multiplayer Matchmaking) ===
//    */
//   btnPVP.addEventListener("click", async () => {
//     try {
//       status.textContent = "Searching for opponent...";
//       btnPVP.disabled = true;
//       // 🔹 Call backend matchmaking endpoint
//       const response = await joinQueue();
//       if (response.success && response.data?.sessionId) {
//         // ✅ Store session data (so waitingUI can access it)
//         setGameState({
//           sessionId: response.data.sessionId,
//           mode: "pvp",
//         });
//         status.textContent = "Matchmaking started! Waiting for another player...";
//         // Redirect to waiting room page
//         window.location.href = "/ludo-game/frontend/waiting.html";
//       } else {
//         // ❌ Error from backend
//         status.textContent =
//           response.message ||
//           "Failed to start multiplayer matchmaking. Please try again.";
//         btnPVP.disabled = false;
//       }
//     } catch {
//       status.textContent = "Something went wrong while starting multiplayer mode.";
//       btnPVP.disabled = false;
//     }
//   });
//   /**
//    * === Player vs AI Mode ===
//    */
//   btnAI.addEventListener("click", () => {
//     alert("⚙️ This feature is under development.\nPlease try 'Play vs Player' mode instead!");
//   });
// }
