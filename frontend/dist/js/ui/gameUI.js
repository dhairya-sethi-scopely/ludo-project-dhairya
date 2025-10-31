// gameUI.ts (Corrected)
import { rollDice, moveToken, getSession } from "../api/gameApi.js";
import { getGameState } from "../core/state.js";
import { redPathHead, bluePathHead, getNodeByStep, getNextPositionByStep } from "../core/boardPath.js";
import { renderTokens } from "../core/boardRenderer.js";
const state = getGameState();
const sessionId = state.sessionId;
const redAndBlueCircles = document.querySelectorAll(".inner_circle.red_bg, .inner_circle.blue_bg");
const rollBtn = document.getElementById("rollDice");
var diceResult = 0;
const movedTokenData = {};
// Loop through them and add click listeners
// Change export function setupGame(): void to export async function setupGame(): Promise<void>
export async function setupGame() {
    console.log(":video_game: Initializing game screen...");
    const diceValueElem = document.getElementById("diceValue");
    const turnElem = document.getElementById("turnInfo");
    if (!rollBtn || !diceValueElem || !turnElem) {
        console.error(":x: Game UI elements not found in DOM.");
        return;
    }
    console.log(":video_game: Game screen initialized.");
    const mode = state.mode || "pvp";
    // :small_blue_diamond: Load current turn info before first dice roll
    // The await here now works because setupGame is an async function
    // try {
    //   const session = await getSession(sessionId);
    //   if (session.success) {
    //     const currentRole = session.next_role ?? "player";
    //     const currentPlayer = session.next_player ?? 1;
    //     const roleLabel = currentRole === "ai" ? ":🤖: AI" : `:🧍‍♂️: Player ${currentPlayer}`;
    //     turnElem.textContent = `Turn: ${roleLabel}`;
    //   } else {
    //     turnElem.textContent = "Turn: Unknown";
    //   }
    // } catch {
    //   turnElem.textContent = "Turn: Not available";
    // }
    renderTokens();
    // Initialize board
    // highlightActiveYard("red");
    // The turn element is already set above, so this line might be redundant or needs logic to check which player starts
    // turnElem.textContent = ":🧍‍♂️: Player's turn (Red)";
    /**
     * :🎲: Dice Roll Logic
    */
    redAndBlueCircles.forEach((circle) => {
        circle.addEventListener("click", handleTokenClick);
    });
    rollBtn.addEventListener("click", async () => {
        rollBtn.disabled = true;
        rollBtn.style.backgroundColor = "gray";
        diceValueElem.textContent = "Rolling :🎲...";
        try {
            const session = await getSession(sessionId);
            console.log("SESSION INFO:", sessionId);
            if (session.success) {
                const currentRole = session.turn ?? "player";
                const currentPlayer = session.players[currentRole];
                const roleLabel = currentPlayer === 9999 ? ":🤖: AI" : `:🧍‍♂️: Player ${currentPlayer}`;
                turnElem.textContent = `Turn: ${roleLabel}`;
            }
            const dice = await rollDice(sessionId);
            if (!dice.success)
                throw new Error(dice.message || "Dice roll failed");
            const diceValue = dice.dice_value ?? 1;
            diceValueElem.textContent = `:🎲: Dice: ${diceValue}`;
            diceResult = diceValue;
            console.log(`Rolled: ${diceValue}`);
            const currentRole = session.turn ?? "player";
            const currentPlayer = session.players[currentRole];
            const roleLabel = currentPlayer === 9999 ? ":🤖: AI" : `:🧍‍♂️: Player ${currentPlayer}`;
            turnElem.textContent = `Turn: ${roleLabel}`;
            if (diceValue !== 6 && session.turn === 0) {
                await new Promise((res) => setTimeout(res, 10000)); // Wait 5 seconds for AI
                AILogic();
            }
        }
        catch (err) {
            console.error("Error:", err.message);
            alert("Error: " + err.message);
        }
        finally {
            setTimeout(() => {
                rollBtn.style.backgroundColor = "";
                rollBtn.disabled = false;
            }, 1000);
        }
    });
}
/**
 * :sparkles: Highlights the active player's yard (home box)
 */
// ... rest of the file ...
/**
 * :sparkles: Highlights the active player's yard (home box)
 */
function highlightActiveYard(color) {
    document.querySelectorAll(".box").forEach((box) => {
        box.style.boxShadow = "none";
        box.style.filter = "grayscale(100%)";
    });
    const activeBox = color === "red"
        ? document.querySelector(".red_home")
        : document.querySelector(".blue_home");
    if (activeBox) {
        activeBox.style.boxShadow = `0 0 30px 8px ${color}`;
        activeBox.style.filter = "none";
    }
}
export async function handleTokenClick(event) {
    console.log("Token clicked:", this.id);
    const tokenId = this.id.trim();
    if (!tokenId) {
        console.warn("Clicked token has no ID.");
        return;
    }
    const color = tokenId.startsWith("P1_") ? "Red" : "Blue";
    console.log(`${color} token clicked! (ID: ${tokenId})`);
    try {
        const session = await getSession(sessionId);
        if (session.turn === 1) {
            console.log("It's AI's turn. Ignoring player input.");
            const move = await moveToken(sessionId, "P9999_T4", diceResult);
            movedTokenData["P9999_T4"] = move;
            console.log("Move response:", move);
            console.log("Moved token data:", movedTokenData);
            frontendMoveToken(sessionId, tokenId, diceResult);
        }
        else {
            console.log("It's player's turn.");
            const move = await moveToken(sessionId, tokenId, diceResult);
            movedTokenData[tokenId] = move;
            console.log("Move response:", move);
            console.log("Moved token data:", movedTokenData);
            frontendMoveToken(sessionId, tokenId, diceResult);
        }
    }
    catch (error) {
        console.error("Error while moving token:", error);
    }
}
function frontendMoveToken(sessionId, tokenId, diceValue) {
    // Step number fetched from your backend state tracking
    let steps = movedTokenData[tokenId].steps;
    if (tokenId.startsWith("P1_")) {
        steps = steps - 1;
    }
    console.log("Steps to move:", steps);
    // Identify which player's path to use
    const isRed = tokenId.startsWith("P1_"); // assuming P1 = Red, P2 = Blue
    const pathHead = isRed ? redPathHead : bluePathHead;
    console.log("Next position:", getNextPositionByStep(isRed ? "red" : "blue", steps, diceValue));
    // Get coordinates (x, y) from the linked list
    const position = getNodeByStep(pathHead, steps);
    console.log("position:", position);
    if (!position) {
        console.warn(`Invalid step index ${steps} for token ${tokenId}`);
        return;
    }
    const { x, y } = position;
    console.log(`:dart: Token ${tokenId} new position → Row: ${x}, Col: ${y}`);
    // Now find the corresponding cell in the grid
    const cell = document.querySelector(`.cell[style*="grid-area: ${x} / ${y}"]`);
    if (!cell) {
        console.warn(`No cell found for grid-area: ${x} / ${y}`);
        return;
    }
    // Move the token (inner_circle) visually into that cell
    const tokenElement = document.getElementById(tokenId);
    if (!tokenElement) {
        console.warn(`Token element with ID ${tokenId} not found`);
        return;
    }
    // Append the token to that cell
    cell.appendChild(tokenElement);
    // Optional animation
    tokenElement.style.transition = "transform 0.3s ease";
    tokenElement.style.transform = "scale(1.1)";
    setTimeout(() => (tokenElement.style.transform = "scale(1)"), 300);
}
async function AILogic() {
    console.log("🤖 AI's turn started.");
    // const AIturn = await triggerAITurn(sessionId);
    const session = await getSession(sessionId);
    // console.log("AI Turn Response:", AIturn);
    // if (!AIturn.success) {
    //   console.error("AI turn failed:", AIturn.message);
    //   alert("AI turn failed: " + AIturn.message);
    //   return;
    // }
    const dice = await rollDice(sessionId);
    if (!dice.success)
        throw new Error(dice.message || "Dice roll failed");
    const diceValue = dice.dice_value;
    const diceValueElem = document.getElementById("diceValue");
    if (diceValueElem) {
        diceValueElem.textContent = `🎲: Dice: ${diceValue}`;
    }
    const currentRole = session.turn ?? "player";
    const turnElem = document.getElementById("turnInfo");
    if (turnElem) {
        const currentPlayer = session.players[currentRole];
        const roleLabel = currentPlayer === 9999 ? ":🤖: AI" : `:🧍‍♂️: Player ${currentPlayer}`;
        turnElem.textContent = `Turn: ${roleLabel}`;
    }
    if (diceValue === 6 && session.game_state.tokens["9999"][3].position == "YARD" && session.turn === 1) {
        handleTokenClick.call(document.getElementById("P9999_T4"), new Event("click"));
    }
    // if ((diceValue === 6 || session.game_state.tokens["9999"][3].position !== "YARD") && session.turn === 1) {
    //   handleTokenClick.call(document.getElementById("P9999_T4") as HTMLDivElement, new Event("click"));
    // }
    if ((session.game_state.tokens["9999"][3].position !== "YARD") && session.turn === 1) {
        handleTokenClick.call(document.getElementById("P9999_T4"), new Event("click"));
    }
    if (diceValue === 6 && session.turn === 1) {
        AILogic();
    }
}
// i have added the button functionality on every div which is having class inner-circle red_bg and inner-circle blue_bg to log which token is clicked but in yard the button functionality should only be available if dice value is 6
