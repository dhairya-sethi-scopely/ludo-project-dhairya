"use strict";
/**
 * main.ts
 * --------
 * Detects which page is loaded and initializes the right UI.
 */
document.addEventListener("DOMContentLoaded", async () => {
    if (document.getElementById("btnRegister")) {
        // 🟢 Load only auth logic dynamically
        const { setupAuthUI } = await import("./ui/authUI.js");
        setupAuthUI();
    }
    else if (document.getElementById("btnPVP")) {
        // 🟢 Load only menu logic dynamically
        const { setupMenu } = await import("./ui/menuUI.js");
        setupMenu();
    }
});
