// Builds the 15×15 board and allows placing tokens
export function initGrid15(targetId = "board") {
    const host = document.getElementById(targetId);
    if (!host) {
        console.error(`[initGrid15] Board container not found`);
        return;
    }
    host.innerHTML = "";
    for (let r = 0; r < 15; r++) {
        for (let c = 0; c < 15; c++) {
            const cell = document.createElement("div");
            cell.className = "board-cell";
            cell.id = `c-${r}-${c}`;
            cell.dataset.row = String(r);
            cell.dataset.col = String(c);
            host.appendChild(cell);
        }
    }
    console.log("✅ 15×15 board generated");
}
export function placeTokenAtCell(row, col, tokenId, owner) {
    const cell = document.getElementById(`c-${row}-${col}`);
    if (!cell)
        return;
    // remove old token
    document.querySelector(`[data-token-id="${tokenId}"]`)?.remove();
    const token = document.createElement("div");
    token.className = `token token--${owner}`;
    token.dataset.tokenId = tokenId;
    token.innerHTML = `<span></span>`;
    cell.appendChild(token);
}
