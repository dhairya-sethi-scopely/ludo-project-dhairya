/**
 * boardRenderer.ts
 * ----------------
 * Controls token visibility and highlighting on the existing 15×15 grid.
 */
/**
 * 🧱 Initialize the board
 * Hides all tokens (sets opacity = 0) and removes highlights.
 */
export function renderTokens() {
    // Hide only path cells
    document.querySelectorAll(".outer .cell .inner_circle").forEach((el) => {
        const e = el;
        e.style.opacity = "0";
        e.style.visibility = "hidden";
    });
    // Ensure yard/home tokens are visible (override any prior inline style)
    document.querySelectorAll(".box .inner_circle").forEach((el) => {
        const e = el;
        e.style.opacity = "1";
        e.style.visibility = "visible";
    });
    console.log("✅ Board initialized: yard tokens visible, path tokens hidden.");
}
/**
 * ✨ Highlights the possible cells a player can click to move a token.
 * Adds a glowing gold effect for clickable cells.
 */
export function highlightMovableTokens(coords) {
    coords.forEach(({ x, y }) => {
        const selector = `.cell[data-x='${x}'][data-y='${y}'] .inner_circle`;
        const circle = document.querySelector(selector);
        if (circle) {
            circle.classList.add("highlight");
            circle.style.boxShadow = "0 0 12px 4px gold";
            circle.style.cursor = "pointer";
        }
    });
}
/**
 * 🟢 Moves a token to a new (x, y) position.
 * Instead of moving elements, we just make the respective `.inner_circle` visible.
 */
export function moveTokenVisual(player, x, y) {
    // Hide all currently visible tokens for that player
    document.querySelectorAll(`.inner_circle.${player}_bg`).forEach((el) => {
        el.style.opacity = "0";
    });
    // Find the new cell
    const selector = `.cell[data-x='${x}'][data-y='${y}'] .inner_circle`;
    const target = document.querySelector(selector);
    if (!target) {
        console.warn(`⚠️ No inner_circle found for position (${x}, ${y})`);
        return;
    }
    // Show and color the token
    target.classList.add(`${player}_bg`);
    target.style.opacity = "1";
    target.style.boxShadow = `0 0 8px 3px ${player}`;
}
/**
 * 🧹 Clears highlights after player move.
 */
export function clearHighlights() {
    document.querySelectorAll(".inner_circle.highlight").forEach((el) => {
        const elHTML = el;
        elHTML.classList.remove("highlight");
        elHTML.style.boxShadow = "";
        elHTML.style.cursor = "";
    });
}
