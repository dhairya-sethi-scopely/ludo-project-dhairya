/**
 * boardMovement.ts
 * ----------------
 * Moves tokens along a linked-list path and updates their visual position.
 */
import { redPathHead } from "./boardPath.js";
/**
 * Finds the Node at a specific step count in a player's path.
 */
export function getNodeAtStep(head, steps) {
    let current = head;
    let count = 0;
    while (current && count < steps) {
        current = current.next;
        count++;
    }
    return current;
}
/**
 * Updates a token’s logical + visual position based on backend steps.
 */
export function updateTokenPosition(token, steps) {
    const head = redPathHead; // later this will depend on token.player
    const node = getNodeAtStep(head, steps);
    token.position = node;
    token.steps = steps;
    token.status = "path";
    // === Update DOM representation ===
    const tokenEl = document.getElementById(token.id);
    const board = document.getElementById("ludoBoard");
    if (tokenEl && board) {
        // Each grid cell ~ 50px (assuming your board is 750px wide for 15x15)
        const cellSize = 750 / 15;
        const xPos = (node.x - 1) * cellSize + cellSize / 3;
        const yPos = (node.y - 1) * cellSize + cellSize / 3;
        tokenEl.style.position = "absolute";
        tokenEl.style.left = `${xPos}px`;
        tokenEl.style.top = `${yPos}px`;
        tokenEl.style.transition = "all 0.5s ease-in-out";
    }
}
