/**
 * resultDeclared.ts
 * -----------------
 * Dynamically displays the final match result for both players.
 */

document.addEventListener("DOMContentLoaded", () => {
  // === Retrieve stored match info ===
  const winnerId = localStorage.getItem("winnerId");
  const gameResult = JSON.parse(localStorage.getItem("gameResult") || "{}");
  const myId = Number(localStorage.getItem("player_id"));
  const myName = localStorage.getItem("username") || `Player_${myId}`;

  const title = document.getElementById("resultTitle") as HTMLElement;
  const winnerText = document.getElementById("winnerText") as HTMLElement;
  const resultList = document.getElementById("resultList") as HTMLElement;

  // === If no data found ===
  if (!winnerId || !gameResult?.game_state) {
    title.textContent = "⚠️ Match Data Missing!";
    winnerText.textContent = "Unable to load result.";
    return;
  }

  const isWinner = myId === Number(winnerId);
  const players = Object.keys(gameResult.game_state.tokens || {});

  // === Winner's username (fetched from gameResult or fallback) ===
  const winnerName =
    gameResult?.winnerName ||
    (Number(winnerId) === myId ? myName : `Player_${winnerId}`);

  // === Update main title ===
  title.textContent = isWinner
    ? "🏆 Congratulations! You Won!"
    : "💔 Better Luck Next Time!";
  title.className = isWinner ? "status-win" : "status-loss";

  // === Winner text ===
  winnerText.textContent = `${winnerName} Wins the Game!`;

  // === Build player result list dynamically ===
  resultList.innerHTML = players
    .map((pid) => {
      const isThisWinner = Number(pid) === Number(winnerId);
      const colorClass = isThisWinner ? "green" : "red";
      const playerLabel =
        Number(pid) === myId ? `${myName} (You)` : `Player_${pid}`;
      const statusText = isThisWinner ? "🏆 Winner!" : "😔 Lost";

      return `
        <li class="${isThisWinner ? "rank-1" : "rank-2"}">
          <span class="ludo-color ${colorClass}"></span>
          <span class="player-name">${playerLabel}</span>
          <span class="status">${statusText}</span>
        </li>
      `;
    })
    .join("");

  // === Button Handlers ===
  const playAgainBtn = document.getElementById("btnPlayAgain");
  const exitBtn = document.getElementById("btnExit");

  playAgainBtn?.addEventListener("click", () => {
    console.log("🔁 Redirecting to menu.html...");
    localStorage.removeItem("gameResult");
    localStorage.removeItem("winnerId");
    window.location.href = "/ludo-game/frontend/menu.html";
  });

  exitBtn?.addEventListener("click", () => {
    console.log("🚪 Exiting to auth.html...");
    localStorage.clear();
    window.location.href = "/ludo-game/frontend/auth.html";
  });
});
