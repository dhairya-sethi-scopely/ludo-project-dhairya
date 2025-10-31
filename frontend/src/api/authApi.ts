/**
 * authApi.ts
 * -----------
 * Handles frontend communication with backend authentication APIs:
 * - register.php
 * - login.php
 * 
 * All requests automatically include credentials (cookies) 
 * to maintain JWT session from the backend.
 */

import { ApiResponse } from "../core/types";

// 👇 Make sure this matches your backend’s localhost path
const BASE_URL = "http://localhost:8888/ludo-game/backend/api/";

/**
 * Utility: Generate SHA-256 client signature.
 * --------------------------------------------
 * This uses the browser's built-in crypto.subtle API.
 * 
 * Example:
 *   hashClientSignature("player101@example.com")
 *     → "a8f93b1d0d3b8f..."
 */
async function hashClientSignature(data: string): Promise<string> {
  const encoder = new TextEncoder();
  const dataBuffer = encoder.encode(data);
  const hashBuffer = await crypto.subtle.digest("SHA-256", dataBuffer);

  // Convert ArrayBuffer to hexadecimal string
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
}

/**
 * Registers a new player.
 * ------------------------------------
 * Sends POST → backend/api/register.php
 * Required body:
 * {
 *   username, email, password, clientHash
 * }
 */
export async function registerUser(
  username: string,
  email: string,
  password: string,
  secretKey: string
): Promise<ApiResponse> {
  // ✅ Create secure signature
  const clientHash = await hashClientSignature(username + email + secretKey);

  const body = {
    username,
    email,
    password,
    clientHash
  };

  const res = await fetch(`${BASE_URL}register.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
    credentials: "include" // includes cookies for session
  });

  const data = await res.json();
  return data;
}

/**
 * Logs in an existing player.
 * ------------------------------------
 * Sends POST → backend/api/login.php
 * Required body:
 * {
 *   username, password, clientHash
 * }
 */
export async function loginUser(
  username: string,
  password: string,
  secretKey: string
): Promise<ApiResponse> {
  // ✅ Hash username + password + secretKey (same as backend)
  const clientHash = await hashClientSignature(username + password + secretKey);

  const body = {
    username,
    password,
    clientHash
  };

  const res = await fetch(`${BASE_URL}login.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
    credentials: "include"
  });

  const data = await res.json();
  return data;
}


