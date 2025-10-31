// // mainPVP.ts — Multiplayer PvP Dice + Token Movement
// import { rollDice, getSession, moveToken } from "../api/gameApi.js";
// import { getGameState } from "../core/state.js";
// import { renderTokens } from "../core/boardRenderer.js";
// import { DiceResponse, MoveResponse } from "../core/types.js";
// import {
//     redPathHead,
//     bluePathHead,
//     getNodeByStep,
// } from "../core/boardPath.js";

// // === Global session setup ===
// const state = getGameState();
// const sessionId = state.sessionId;

// const rollBtn = document.getElementById("rollDice") as HTMLButtonElement | null;
// const diceValueElem = document.getElementById("diceValue") as HTMLElement | null;
// const turnElem = document.getElementById("turnInfo") as HTMLElement | null;

// // === Token elements ===
// const redAndBlueCircles = document.querySelectorAll<HTMLDivElement>(
//     ".inner_circle.red_bg, .inner_circle.blue_bg"
// );

// // === Game state variables ===
// let turnIndex = 0;
// let players: number[] = [];
// let myId: number | null = null;
// let diceResult = 0;
// let validTokens: string[] = [];
// let movePhaseActive = false;
// const movedTokenData: Record<string, any> = {};

// // ========================================================
// // 🎮 Initialize the PvP game screen
// // ========================================================
// export async function setupGame(): Promise<void> {
//     if (!rollBtn || !diceValueElem || !turnElem) {
//         console.error("❌ Missing essential DOM elements.");
//         return;
//     }

//     console.log("🎯 Multiplayer PvP initialized.");

//     await syncGameState();
//     // 🧹 Reset token HOME state flags (so they animate again in a new game)
//     document.querySelectorAll(".inner_circle").forEach((token) => {
//         token.removeAttribute("data-in-home");
//     });
//     renderTokens();

//     // 🎲 === DICE ROLL HANDLER ===
//     rollBtn.addEventListener("click", async () => {
//         rollBtn.disabled = true;
//         rollBtn.style.backgroundColor = "gray";
//         diceValueElem.textContent = "Rolling 🎲...";

//         try {
//             const dice: DiceResponse = await rollDice(sessionId);
//             if (!dice.success) throw new Error(dice.message || "Dice roll failed");

//             diceResult = Number(dice.dice_value);
//             diceValueElem.textContent = `🎲 Dice: ${diceResult}`;
//             console.log(`Rolled: ${diceResult}`);

//             // ✅ If backend sends valid tokens, enter move phase
//             if (dice.valid_tokens && dice.valid_tokens.length > 0) {
//                 validTokens = dice.valid_tokens;
//                 movePhaseActive = true;
//                 console.log("Eligible tokens:", validTokens);
//             } else {
//                 validTokens = [];
//                 movePhaseActive = false;
//                 console.log("No tokens can move this turn.");
//             }
//         } catch (err: any) {
//             console.error("Dice roll error:", err.message);
//             alert("Error: " + err.message);
//         } finally {
//             setTimeout(() => {
//                 rollBtn.style.backgroundColor = "";
//                 rollBtn.disabled = movePhaseActive; // stays disabled until token is moved
//             }, 800);
//         }
//     });

//     // 🎯 === TOKEN CLICK HANDLER ===
//     redAndBlueCircles.forEach((circle) => {
//         circle.addEventListener("click", handleTokenClick);
//     });

//     // 🔁 === POLLING (every 2s) ===
//     setInterval(syncGameState, 2000);
// }

// // ========================================================
// // 🔄 Fetch latest session state from backend
// // ========================================================
// async function syncGameState() {
//     try {
//         const res = await getSession(sessionId);
//         if (!res.success) return;

//         // --- Update players and turn ---
//         players = res.players?.map((p: any) => Number(p)) ?? players;
//         turnIndex = Number(res.turn ?? 0);

//         const currentPlayer = players[turnIndex];
//         myId = Number(res.me);
//         const isMyTurn = myId === currentPlayer;

//         // --- Update turn info ---
//         turnElem!.textContent = isMyTurn
//             ? "🎯 Your Turn"
//             : `⏳ Opponent's Turn (Player ${currentPlayer})`;

//         // --- Dice button control ---
//         rollBtn!.disabled = !isMyTurn || movePhaseActive;
//         rollBtn!.style.opacity = rollBtn!.disabled ? "0.5" : "1";

//         // --- Dice display ---
//         const diceValue = res.game_state?.lastDice ?? null;
//         diceValueElem!.textContent = diceValue
//             ? `🎲 Dice: ${diceValue}`
//             : "🎲 Dice: -";

//         // --- Token positions sync ---
//         if (res.game_state && res.game_state.tokens) {
//             const allTokens = res.game_state.tokens;
//             console.group(
//                 `🔄 SYNC STATE [Player ${myId}] – CurrentTurn=${currentPlayer}`
//             );

//             Object.entries(allTokens).forEach(([pid, tokenList]: [string, any]) => {
//                 tokenList.forEach((token: any) => {
//                     const tokenId = token.id;
//                     const steps = Number(token.steps ?? 0);
//                     const pos = token.position ?? "YARD";


//                     console.log(`→ ${tokenId}: pos=${pos}, steps=${steps}`);

//                     if (pos === "YARD") {
//                         resetTokenToYard(tokenId);
//                     } else if (pos === "PATH") {
//                         const tokenEl = document.getElementById(tokenId);
//                         if (!tokenEl) return;
//                         tokenEl.style.top = "0%";
//                         // tokenEl.style.right = "-2";
//                         // tokenEl.style.bottom = "-2";
//                         tokenEl.style.left = "0%";
//                         tokenEl.style.margin = "10px 0 0 9.5px";
//                         updateTokenPosition(tokenId, steps);
//                     }
//                     else if (pos === "HOME") {
//                         moveTokenToHome(tokenId); // 🏁 new helper
//                     }
//                 });
//             });

//             console.groupEnd();
//         }
//         // --- 🏁 Check for Game Over ---
//         if (res.game_state?.isGameOver) {
//             console.log("🏁 Game ended — showing result screen");

//             // store winner and final data for both players
//             localStorage.setItem("winnerId", String(res.game_state.winner));
//             localStorage.setItem("gameResult", JSON.stringify(res));

//             // redirect both players to the result screen
//             window.location.href = "/ludo-game/frontend/resultScreen.html";
//             return;
//         }
//     } catch (err) {
//         console.error("❌ Error syncing game state:", err);
//     }
// }

// // ========================================================
// // 🧩 Move token visually to (x, y) on board
// // ========================================================
// function updateTokenPosition(tokenId: string, steps: number) {
//     const isRed = tokenId.startsWith("P1_");
//     const pathHead = isRed ? redPathHead : bluePathHead;

//     console.group(`🔍 Updating Token: ${tokenId}`);
//     console.log(`→ Steps: ${steps}`);

//     const node = getNodeByStep(pathHead, steps);
//     if (!node) {
//         console.warn(`⚠️ No node found for ${tokenId} at step ${steps}`);
//         console.groupEnd();
//         return;
//     }

//     const { x, y } = node;
//     console.log(`→ Node found at: x=${x}, y=${y}`);

//     // Locate the cell
//     const cell = Array.from(document.querySelectorAll(".cell")).find((el) => {
//         const style = (el as HTMLElement).getAttribute("style") ?? "";
//         return style.includes(`grid-area: ${x} / ${y}`);
//     }) as HTMLElement | undefined;

//     if (!cell) {
//         console.warn(`⚠️ No cell found for grid-area: ${x} / ${y}`);
//         console.groupEnd();
//         return;
//     }

//     const tokenEl = document.getElementById(tokenId);
//     if (!tokenEl) {
//         console.warn(`⚠️ Token element not found for ID: ${tokenId}`);
//         console.groupEnd();
//         return;
//     }

//     if (tokenEl.parentElement !== cell) {
//         cell.appendChild(tokenEl);
//         console.log(`✅ Token placed at (${x}, ${y})`);
//         tokenEl.style.transition = "transform 0.3s ease";
//         tokenEl.style.transform = "scale(1.1)";
//         setTimeout(() => (tokenEl.style.transform = "scale(1)"), 300);
//     }

//     console.log(`ℹ️ Final position → step=${steps}, grid=(${y}/${x})`);
//     console.groupEnd();
// }


// // ========================================================
// // 🧩 Reset token visually to its yard (home)
// // ========================================================
// // function resetTokenToYard(tokenId: string) {
// //     const tokenEl = document.getElementById(tokenId);
// //     if (!tokenEl) return;

// //     // Identify the correct yard
// //     const circleBorder = tokenId.startsWith("P1_")
// //         ? document.querySelector(".red_home")
// //         : document.querySelector(".blue_home");

// //     if (!circleBorder) return;

// //     // Only move if not already in yard
// //     if (tokenEl.parentElement !== circleBorder) {
// //         circleBorder.appendChild(tokenEl);
// //         console.log(`🏠 ${tokenId} returned to yard`);
// //     }
// // }
// function resetTokenToYard(tokenId: string) {
//     const tokenEl = document.getElementById(tokenId);
//     if (!tokenEl) return;

//     // Determine player and token number
//     let circleId = "";
//     if (tokenId.startsWith("P1_")) circleId = "rc" + tokenId.split("_T")[1];

//     else if (tokenId.startsWith("P2_")) circleId = "bc" + tokenId.split("_T")[1];

//     const targetCircle = document.getElementById(circleId);
//     if (!targetCircle) {
//         console.warn(`⚠️ Yard circle not found for ${tokenId} (expected ${circleId})`);
//         return;
//     }

//     // Append token to correct base
//     if (tokenEl.parentElement !== targetCircle) {
//         targetCircle.appendChild(tokenEl);

//         // Center token neatly inside circle
//         // tokenEl.style.position = "absolute";
//         // if (tokenId.p)
//         // tokenEl.style.top = "50%";
//         // tokenEl.style.left = "50%";
//         // tokenEl.style.transform = "translate(-50%, -50%)";

//         console.log(`🏠 ${tokenId} returned to ${circleId}`);
//     }
// }

// function moveTokenToHome(tokenId: string) {
//     const tokenEl = document.getElementById(tokenId);
//     if (!tokenEl) return;

//     // 🧠 Skip if already synced to home (prevent repeated animation)
//     if (tokenEl.dataset.inHome === "true") return;
//     tokenEl.dataset.inHome = "true"; // mark as already moved to home

//     const homeArea = document.querySelector(".ludo_home_container");
//     if (!homeArea) return;

//     // Style + random offset to avoid overlap
//     tokenEl.style.position = "absolute";
//     tokenEl.style.width = "25px";
//     tokenEl.style.height = "25px";
//     tokenEl.style.border = "2px solid #fff";
//     tokenEl.style.borderRadius = "50%";
//     tokenEl.style.zIndex = "10";

//     const offsetX = (Math.random() - 0.5) * 60;
//     const offsetY = (Math.random() - 0.5) * 60;
//     tokenEl.style.top = `calc(50% + ${offsetY}px)`;
//     tokenEl.style.left = `calc(50% + ${offsetX}px)`;
//     tokenEl.style.transform = "translate(-50%, -50%)";

//     // Animate once
//     tokenEl.animate(
//         [
//             { transform: "translate(-50%, -50%) scale(1.4)", opacity: 0.5 },
//             { transform: "translate(-50%, -50%) scale(1)", opacity: 1 }
//         ],
//         { duration: 400, easing: "ease-out" }
//     );

//     homeArea.appendChild(tokenEl);
//     homeArea.classList.add("glow");

//     // glow only once for 2 seconds
//     setTimeout(() => homeArea.classList.remove("glow"), 2000);

//     console.log(`🏁 Token ${tokenId} synced into HOME`);
// }


// // ========================================================
// // 🧩 Handle token click (move phase)
// // ========================================================
// export async function handleTokenClick(this: HTMLDivElement, event: Event) {
//     const tokenId = this.id.trim();
//     if (!tokenId) {
//         console.warn("⚠️ Clicked token has no ID.");
//         return;
//     }

//     console.log("🎯 Token clicked:", tokenId);

//     try {
//         const session = await getSession(sessionId);
//         const players = session.players.map((p: any) => Number(p));
//         const turnIndex = Number(session.turn ?? 0);
//         const myId = Number(session.me);
//         const currentPlayer = players[turnIndex];

//         if (myId !== currentPlayer) {
//             console.warn("⏳ Not your turn — ignoring click.");
//             return;
//         }

//         if (!movePhaseActive || !validTokens.includes(tokenId)) {
//             console.warn("🚫 Token not eligible to move or move phase inactive.");
//             return;
//         }

//         console.log(`✅ Moving token ${tokenId} with dice ${diceResult}`);
//         const move: MoveResponse = await moveToken(sessionId, tokenId, diceResult);

//         if (move.success) {
//             movedTokenData[tokenId] = move;
//             console.log("✅ Move successful:", move);

//             frontendMoveToken(tokenId, move.steps);

//             // 🏁 Check for WIN condition
//             if (move.game_state?.winner || move.message?.includes("won")) {
//                 console.log("🏆 Game Over! Winner detected.");
//                 localStorage.setItem("winnerId", String(move.player_id));
//                 localStorage.setItem("gameResult", JSON.stringify(move));
//                 window.location.href = "/ludo-game/frontend/resultScreen.html";
//                 return;
//             }
//             // ✅ If capture happened, reset opponent token visually
//             if (move.message?.includes("captured")) {
//                 console.log("🎯 Capture detected!");
//                 await syncGameState(); // refresh both players' token positions
//             }

//             // ✅ Bonus turn handling
//             if (move.message?.includes("Bonus turn")) {
//                 console.log("🏆 Bonus turn for this player!");
//                 rollBtn!.disabled = false;
//                 movePhaseActive = false;
//                 return;
//             }
//             movePhaseActive = false;
//             validTokens = [];
//             await syncGameState();
//         } else {
//             console.warn("❌ Move failed:", move.message);
//         }
//     } catch (error) {
//         console.error("💥 Error while moving token:", error);
//     }
// }

// // ========================================================
// // 🎬 Visual Token Movement (frontend animation)
// // ========================================================
// function frontendMoveToken(tokenId: string, steps: number) {
//     const isRed = tokenId.startsWith("P1_");
//     const pathHead = isRed ? redPathHead : bluePathHead;
//     const tokenEl = document.getElementById(tokenId);
//     if (!tokenEl) return;

//     // === 🏁 57th step → token reaches HOME (center)
//     if (steps >= 57) {
//         const homeArea = document.querySelector(".ludo_home_container") as HTMLElement | null;
//         if (!homeArea) return;

//         homeArea.appendChild(tokenEl);
//         tokenEl.style.position = "absolute";
//         tokenEl.style.width = "25px";
//         tokenEl.style.height = "25px";
//         tokenEl.style.border = "2px solid #fff";
//         tokenEl.style.borderRadius = "50%";
//         tokenEl.style.zIndex = "10";

//         homeArea.classList.add("glow");
//         setTimeout(() => homeArea.classList.remove("glow"), 2500);

//         // slight offset so tokens don't overlap
//         const offsetX = (Math.random() - 0.5) * 60;
//         const offsetY = (Math.random() - 0.5) * 60;
//         tokenEl.style.top = `calc(50% + ${offsetY}px)`;
//         tokenEl.style.left = `calc(50% + ${offsetX}px)`;
//         tokenEl.style.transform = "translate(-50%, -50%)";

//         // entrance animation
//         tokenEl.animate(
//             [
//                 { transform: "translate(-50%, -50%) scale(1.5)", opacity: 0.5 },
//                 { transform: "translate(-50%, -50%) scale(1)", opacity: 1 }
//             ],
//             { duration: 400, easing: "ease-out" }
//         );

//         console.log(`🏁 ${tokenId} reached HOME (step 57)`);
//         return;
//     }

//     // === normal board path ===
//     const node = getNodeByStep(pathHead, steps - 1);
//     if (!node) return;

//     let { x, y } = node;

//     // fix initial entry offset
//     if (isRed && y === 2 && x === 7) y = 1;
//     if (!isRed && y === 7 && x === 14) x = 15;

//     const cell = Array.from(document.querySelectorAll(".cell")).find((el) => {
//         const style = (el as HTMLElement).getAttribute("style") ?? "";
//         return style.includes(`grid-area: ${x} / ${y}`);
//     }) as HTMLElement | undefined;

//     if (!cell) return;

//     if (tokenEl.parentElement !== cell) {
//         cell.appendChild(tokenEl);
//         tokenEl.style.transition = "transform 0.3s ease";
//         tokenEl.style.transform = "scale(1.15)";
//         tokenEl.style.verticalAlign = "middle";
//         setTimeout(() => (tokenEl.style.transform = "scale(1)"), 300);
//     }

//     console.log(`✅ ${tokenId} moved → (${x}, ${y}) [step=${steps}]`);
// }


// // ========================================================
// // 🚀 Initialize game
// // ========================================================
// setupGame();

// mainPVP.ts — Multiplayer PvP Dice + Token Movement
import { rollDice, getSession, moveToken } from "../api/gameApi.js";
import { getGameState } from "../core/state.js";
import { renderTokens } from "../core/boardRenderer.js";
import { DiceResponse, MoveResponse } from "../core/types.js";
import {
  redPathHead,
  bluePathHead,
  getNodeByStep,
} from "../core/boardPath.js";

const state = getGameState();
const sessionId = state.sessionId;

const rollBtn = document.getElementById("rollDice") as HTMLButtonElement | null;
const diceValueElem = document.getElementById("diceValue") as HTMLElement | null;
const turnElem = document.getElementById("turnInfo") as HTMLElement | null;
const redAndBlueCircles = document.querySelectorAll<HTMLDivElement>(
  ".inner_circle.red_bg, .inner_circle.blue_bg"
);

let turnIndex = 0;
let players: number[] = [];
let myId: number | null = null;
let diceResult = 0;
let validTokens: string[] = [];
let movePhaseActive = false;
const movedTokenData: Record<string, any> = {};

// ========================================================
// Initialize Game
// ========================================================
export async function setupGame(): Promise<void> {
  if (!rollBtn || !diceValueElem || !turnElem) return;

  await syncGameState();
  document.querySelectorAll(".inner_circle").forEach((t) => {
    t.removeAttribute("data-in-home");
  });
  renderTokens();

  rollBtn.addEventListener("click", async () => {
    rollBtn.disabled = true;
    rollBtn.style.backgroundColor = "gray";
    diceValueElem.textContent = "Rolling...";

    try {
      const dice: DiceResponse = await rollDice(sessionId);
      if (!dice.success) throw new Error(dice.message || "Dice roll failed");

      diceResult = Number(dice.dice_value);
      diceValueElem.textContent = `Dice: ${diceResult}`;

      if (dice.valid_tokens && dice.valid_tokens.length > 0) {
        validTokens = dice.valid_tokens;
        movePhaseActive = true;
      } else {
        validTokens = [];
        movePhaseActive = false;
      }
    } catch (err: any) {
      alert("Error: " + err.message);
    } finally {
      setTimeout(() => {
        rollBtn.style.backgroundColor = "";
        rollBtn.disabled = movePhaseActive;
      }, 800);
    }
  });

  redAndBlueCircles.forEach((circle) => {
    circle.addEventListener("click", handleTokenClick);
  });

  setInterval(syncGameState, 2000);
}

// ========================================================
// Fetch & Sync Game State
// ========================================================
async function syncGameState() {
  try {
    const res = await getSession(sessionId);
    if (!res.success) return;

    players = res.players?.map((p: any) => Number(p)) ?? players;
    turnIndex = Number(res.turn ?? 0);

    const currentPlayer = players[turnIndex];
    myId = Number(res.me);
    const isMyTurn = myId === currentPlayer;

    turnElem!.textContent = isMyTurn
      ? "Your Turn"
      : `Opponent's Turn (Player ${currentPlayer})`;

    rollBtn!.disabled = !isMyTurn || movePhaseActive;
    rollBtn!.style.opacity = rollBtn!.disabled ? "0.5" : "1";

    const diceValue = res.game_state?.lastDice ?? null;
    diceValueElem!.textContent = diceValue
      ? `Dice: ${diceValue}`
      : "Dice: -";

    if (res.game_state && res.game_state.tokens) {
      const allTokens = res.game_state.tokens;

      Object.entries(allTokens).forEach(([_, tokenList]: [string, any]) => {
        tokenList.forEach((token: any) => {
          const tokenId = token.id;
          const steps = Number(token.steps ?? 0);
          const pos = token.position ?? "YARD";

          if (pos === "YARD") resetTokenToYard(tokenId);
          else if (pos === "PATH") updateTokenPosition(tokenId, steps);
          else if (pos === "HOME") moveTokenToHome(tokenId);
        });
      });
    }

    if (res.game_state?.isGameOver) {
      localStorage.setItem("winnerId", String(res.game_state.winner));
      localStorage.setItem("gameResult", JSON.stringify(res));
      window.location.href = "/ludo-game/frontend/resultScreen.html";
      return;
    }
  } catch (err) {
    console.error("Error syncing game state:", err);
  }
}

// ========================================================
// Update Token Position
// ========================================================
function updateTokenPosition(tokenId: string, steps: number) {
  const isRed = tokenId.startsWith("P1_");
  const pathHead = isRed ? redPathHead : bluePathHead;
  const node = getNodeByStep(pathHead, steps);
  if (!node) return;

  const { x, y } = node;
  const cell = Array.from(document.querySelectorAll(".cell")).find((el) => {
    const style = (el as HTMLElement).getAttribute("style") ?? "";
    return style.includes(`grid-area: ${x} / ${y}`);
  }) as HTMLElement | undefined;

  const tokenEl = document.getElementById(tokenId);
  if (!cell || !tokenEl) return;

  if (tokenEl.parentElement !== cell) {
    cell.appendChild(tokenEl);
    tokenEl.style.transition = "transform 0.3s ease";
    tokenEl.style.transform = "scale(1.1)";
    setTimeout(() => (tokenEl.style.transform = "scale(1)"), 300);
  }
}

// ========================================================
// Reset Token to Yard
// ========================================================
function resetTokenToYard(tokenId: string) {
  const tokenEl = document.getElementById(tokenId);
  if (!tokenEl) return;

  let circleId = "";
  if (tokenId.startsWith("P1_")) circleId = "rc" + tokenId.split("_T")[1];
  else if (tokenId.startsWith("P2_")) circleId = "bc" + tokenId.split("_T")[1];

  const targetCircle = document.getElementById(circleId);
  if (targetCircle && tokenEl.parentElement !== targetCircle) {
    targetCircle.appendChild(tokenEl);
  }
}

// ========================================================
// Move Token to Home (Center)
// ========================================================
function moveTokenToHome(tokenId: string) {
  const tokenEl = document.getElementById(tokenId);
  if (!tokenEl || tokenEl.dataset.inHome === "true") return;

  tokenEl.dataset.inHome = "true";
  const homeArea = document.querySelector(".ludo_home_container");
  if (!homeArea) return;

  tokenEl.style.position = "absolute";
  tokenEl.style.width = "25px";
  tokenEl.style.height = "25px";
  tokenEl.style.border = "2px solid #fff";
  tokenEl.style.borderRadius = "50%";
  tokenEl.style.zIndex = "10";

  const offsetX = (Math.random() - 0.5) * 60;
  const offsetY = (Math.random() - 0.5) * 60;
  tokenEl.style.top = `calc(50% + ${offsetY}px)`;
  tokenEl.style.left = `calc(50% + ${offsetX}px)`;
  tokenEl.style.transform = "translate(-50%, -50%)";

  tokenEl.animate(
    [
      { transform: "translate(-50%, -50%) scale(1.4)", opacity: 0.5 },
      { transform: "translate(-50%, -50%) scale(1)", opacity: 1 },
    ],
    { duration: 400, easing: "ease-out" }
  );

  homeArea.appendChild(tokenEl);
  homeArea.classList.add("glow");
  setTimeout(() => homeArea.classList.remove("glow"), 2000);
}

// ========================================================
// Handle Token Click (Movement)
// ========================================================
export async function handleTokenClick(this: HTMLDivElement) {
  const tokenId = this.id.trim();
  if (!tokenId) return;

  try {
    const session = await getSession(sessionId);
    const players = session.players.map((p: any) => Number(p));
    const turnIndex = Number(session.turn ?? 0);
    const myId = Number(session.me);
    const currentPlayer = players[turnIndex];

    if (myId !== currentPlayer || !movePhaseActive || !validTokens.includes(tokenId)) {
      return;
    }

    const move: MoveResponse = await moveToken(sessionId, tokenId, diceResult);

    if (move.success) {
      movedTokenData[tokenId] = move;
      frontendMoveToken(tokenId, move.steps);

      if (move.game_state?.winner || move.message?.includes("won")) {
        localStorage.setItem("winnerId", String(move.player_id));
        localStorage.setItem("gameResult", JSON.stringify(move));
        window.location.href = "/ludo-game/frontend/resultScreen.html";
        return;
      }

      movePhaseActive = false;
      validTokens = [];
      await syncGameState();
    }
  } catch (error) {
    console.error("Error while moving token:", error);
  }
}

// ========================================================
// Visual Frontend Move Animation
// ========================================================
function frontendMoveToken(tokenId: string, steps: number) {
  const isRed = tokenId.startsWith("P1_");
  const pathHead = isRed ? redPathHead : bluePathHead;
  const tokenEl = document.getElementById(tokenId);
  if (!tokenEl) return;

  if (steps >= 57) {
    const homeArea = document.querySelector(".ludo_home_container") as HTMLElement | null;
    if (!homeArea) return;

    homeArea.appendChild(tokenEl);
    tokenEl.style.position = "absolute";
    tokenEl.style.width = "25px";
    tokenEl.style.height = "25px";
    tokenEl.style.border = "2px solid #fff";
    tokenEl.style.borderRadius = "50%";
    tokenEl.style.zIndex = "10";

    homeArea.classList.add("glow");
    setTimeout(() => homeArea.classList.remove("glow"), 2500);

    const offsetX = (Math.random() - 0.5) * 60;
    const offsetY = (Math.random() - 0.5) * 60;
    tokenEl.style.top = `calc(50% + ${offsetY}px)`;
    tokenEl.style.left = `calc(50% + ${offsetX}px)`;
    tokenEl.style.transform = "translate(-50%, -50%)";

    tokenEl.animate(
      [
        { transform: "translate(-50%, -50%) scale(1.5)", opacity: 0.5 },
        { transform: "translate(-50%, -50%) scale(1)", opacity: 1 },
      ],
      { duration: 400, easing: "ease-out" }
    );
    return;
  }

  const node = getNodeByStep(pathHead, steps - 1);
  if (!node) return;

  const { x, y } = node;
  const cell = Array.from(document.querySelectorAll(".cell")).find((el) => {
    const style = (el as HTMLElement).getAttribute("style") ?? "";
    return style.includes(`grid-area: ${x} / ${y}`);
  }) as HTMLElement | undefined;

  if (!cell) return;
  if (tokenEl.parentElement !== cell) {
    cell.appendChild(tokenEl);
    tokenEl.style.transition = "transform 0.3s ease";
    tokenEl.style.transform = "scale(1.15)";
    setTimeout(() => (tokenEl.style.transform = "scale(1)"), 300);
  }
}

setupGame();
