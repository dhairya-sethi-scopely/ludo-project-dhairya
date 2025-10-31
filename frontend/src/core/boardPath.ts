/**
 * boardPath.ts
 * -------------
 * Represents the Ludo board path using a linked-list structure.
 * Each Node maps to one playable grid cell on the board.
 * 
 * Supports:
 *  - Linked list structure (for future dynamic traversal)
 *  - Step-based lookup via array (used by frontend visuals)
 */

export class Node {
  id: number;        // Unique ID for this cell
  x: number;         // Column position in grid (1–15)
  y: number;         // Row position in grid (1–15)
  next: Node | null; // Pointer to next cell

  constructor(id: number, x: number, y: number) {
    this.id = id;
    this.x = x;
    this.y = y;
    this.next = null;
  }
}

/**
 * 🧩 Builds a linked list path from coordinate list
 */
export function buildPath(coords: { x: number; y: number }[]): Node {
  if (!coords.length) throw new Error("No coordinates provided for path.");

  const head = new Node(0, coords[0].x, coords[0].y);
  let current = head;

  for (let i = 1; i < coords.length; i++) {
    const node = new Node(i, coords[i].x, coords[i].y);
    current.next = node;
    current = node;
  }
  printPath(head);
  return head;
}

/**
 * 🧭 Converts linked list → coordinate array
 */
export function convertToArray(head: Node): { x: number; y: number }[] {
  const arr: { x: number; y: number }[] = [];
  let current: Node | null = head;

  while (current) {
    arr.push({ x: current.x, y: current.y });
    current = current.next;
  }
  return arr;
}

/**
 * 🔢 Finds coordinate by step index (0-based)
 */
export function getNodeByStep(head: Node, step: number): { x: number; y: number } | null {
  let current: Node | null = head;
  let count = 1; // start counting from 1 (first step = head)
  
  while (current) {
    if (count === step) return { x: current.x, y: current.y };
    current = current.next;
    count++;
  }

  return null;
}


/* -----------------------------
 * PLAYER PATH DEFINITIONS
 * ----------------------------- */

/** 🔴 RED PLAYER PATH (P1) */
export const redPathHead = buildPath([
  { x: 7, y: 2 }, { x: 7, y: 3 }, { x: 7, y: 4 }, { x: 7, y: 5 }, { x: 7, y: 6 },
  { x: 6, y: 7 }, { x: 5, y: 7 }, { x: 4, y: 7 }, { x: 3, y: 7 }, { x: 2, y: 7 }, { x: 1, y: 7 },
  { x: 1, y: 8 },
  { x: 1, y: 9 }, { x: 2, y: 9 }, { x: 3, y: 9 }, { x: 4, y: 9 }, { x: 5, y: 9 }, { x: 6, y: 9 },
  { x: 7, y: 10 }, { x: 7, y: 11 }, { x: 7, y: 12 }, { x: 7, y: 13 }, { x: 7, y: 14 }, { x: 7, y: 15 },
  { x: 8, y: 15 },
  { x: 9, y: 15 }, { x: 9, y: 14 }, { x: 9, y: 13 }, { x: 9, y: 12 }, { x: 9, y: 11 }, { x: 9, y: 10 },
  { x: 10, y: 9 }, { x: 11, y: 9 }, { x: 12, y: 9 }, { x: 13, y: 9 }, { x: 14, y: 9 }, { x: 15, y: 9 },
  { x: 15, y: 8 },
  { x: 15, y: 7 }, { x: 14, y: 7 }, { x: 13, y: 7 }, { x: 12, y: 7 }, { x: 11, y: 7 }, { x: 10, y: 7 },
  { x: 9, y: 6 }, { x: 9, y: 5 }, { x: 9, y: 4 }, { x: 9, y: 3 }, { x: 9, y: 2 }, { x: 9, y: 1 },
  { x: 8, y: 1 }, { x: 8, y: 2 }, { x: 8, y: 3 }, { x: 8, y: 4 }, { x: 8, y: 5 }, { x: 8, y: 6 },
  { x: 8, y: 7 }
]);

/** 🔵 BLUE PLAYER PATH (P2) */
export const bluePathHead = buildPath([
  { x: 14, y: 7 }, { x: 13, y: 7 }, { x: 12, y: 7 }, { x: 11, y: 7 }, { x: 10, y: 7 },
  { x: 9, y: 6 }, { x: 9, y: 5 }, { x: 9, y: 4 }, { x: 9, y: 3 }, { x: 9, y: 2 }, { x: 9, y: 1 },
  { x: 8, y: 1 },
  { x: 7, y: 1 }, { x: 7, y: 2 }, { x: 7, y: 3 }, { x: 7, y: 4 }, { x: 7, y: 5 }, { x: 7, y: 6 },
  { x: 6 ,y: 7 }, { x: 5, y: 7 }, { x: 4, y: 7 }, { x: 3, y: 7 }, { x: 2, y: 7 }, { x: 1, y: 7 },
  { x: 1, y: 8 },
  { x: 1, y: 9 }, { x: 2, y: 9 }, { x: 3, y: 9 }, { x: 4, y: 9 }, { x: 5, y: 9 }, { x: 6, y: 9 },
  { x: 7, y: 10 }, { x: 7, y: 11 }, { x: 7, y: 12 }, { x: 7, y: 13 }, { x: 7, y: 14 }, { x: 7, y: 15 },
  { x: 8, y: 15 },
  { x: 9, y: 15 }, { x: 9, y: 14 }, { x: 9, y: 13 }, { x: 9, y: 12 }, { x: 9, y: 11 }, { x: 9, y: 10 },
  { x: 10, y: 9 }, { x: 11, y: 9 }, { x: 12, y: 9 }, { x: 13, y: 9 }, { x: 14, y: 9 }, { x: 15, y: 9 },
  { x: 15, y: 8 },
  { x: 14, y: 8 }, { x: 13, y: 8 }, { x: 12, y: 8 }, { x: 11, y: 8 }, { x: 10, y: 8 }, { x: 9, y: 8 },
  { x: 8, y: 8 }
]);

export function printPath(head: Node): void {
  let current: Node | null = head;
  const pathCoords: string[] = [];

  while (current) {
    pathCoords.push(`(${current.x},${current.y})`);
    current = current.next;
  }

  console.log("Path:", pathCoords.join(" -> "));
}

// Convert to arrays for faster random access
export const redPathArray = convertToArray(redPathHead);
export const bluePathArray = convertToArray(bluePathHead);

/**
 * 🎯 Returns coordinate (x, y) for a given player's step number
 */
export function getPositionByStep(player: "red" | "blue", step: number): { x: number; y: number } | null {
  const pathArray = player === "red" ? redPathArray : bluePathArray;
  return pathArray[step] || null;
}

export function getNextPositionByStep(
  player: "red" | "blue",
  currentStep: number,
  stepsToMove: number
): { x: number; y: number } | null {
  const pathArray = player === "red" ? redPathArray : bluePathArray;
  const nextStep = currentStep + stepsToMove;
  return pathArray[nextStep] || null;
}
