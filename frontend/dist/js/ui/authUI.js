/**
 * authUI.ts
 * ----------
 * Handles Register/Login UI interactions.
 * Redirects to `menu.html` after successful login.
 */
import { registerUser, loginUser } from "../api/authApi.js";
export function setupAuthUI() {
    const registerBtn = document.getElementById("btnRegister");
    const loginBtn = document.getElementById("btnLogin");
    const secretKey = "test_secret_key"; // Must match backend config.local.json
    // === REGISTER HANDLER ===
    registerBtn?.addEventListener("click", async () => {
        const username = document.getElementById("regUsername").value.trim();
        const email = document.getElementById("regEmail").value.trim();
        const password = document.getElementById("regPassword").value.trim();
        if (!username || !email || !password) {
            alert("Please fill in all registration fields.");
            return;
        }
        registerBtn.disabled = true;
        registerBtn.textContent = "Registering...";
        try {
            const result = await registerUser(username, email, password, secretKey);
            if (result.success) {
                alert("Registration successful! You can now log in.");
            }
            else {
                alert(result.message || "Registration failed. Please try again.");
            }
        }
        catch {
            alert("Network or server error while registering.");
        }
        finally {
            registerBtn.disabled = false;
            registerBtn.textContent = "Register";
        }
    });
    // === LOGIN HANDLER ===
    loginBtn?.addEventListener("click", async () => {
        const username = document.getElementById("loginUsername").value.trim();
        const password = document.getElementById("loginPassword").value.trim();
        if (!username || !password) {
            alert("Please enter both username and password.");
            return;
        }
        loginBtn.disabled = true;
        loginBtn.textContent = "Logging in...";
        try {
            const result = await loginUser(username, password, secretKey);
            if (result.success) {
                alert("Login successful!");
                localStorage.setItem("username", username);
                localStorage.setItem("player_id", "1"); // placeholder — replace later with backend user ID
                setTimeout(() => {
                    window.location.href = "./menu.html";
                }, 1000); // shorter delay for smoother UX
            }
            else {
                alert(result.message || "Invalid credentials.");
            }
        }
        catch {
            alert("Network or server error while logging in.");
        }
        finally {
            loginBtn.disabled = false;
            loginBtn.textContent = "Login";
        }
    });
}
// === Initialize ===
document.addEventListener("DOMContentLoaded", setupAuthUI);
