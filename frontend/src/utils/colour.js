const COLOURS = [
  "#1b3a6b",
  "#0e6b5e",
  "#6b3a1b",
  "#3a1b6b",
  "#1b6b3a",
  "#6b1b3a",
  "#1b556b",
  "#6b5e1b",
  "#2d4a8a",
  "#8a2d2d",
  "#2d8a6b",
  "#8a6b2d",
];

function hashString(str) {
  let hash = 0;
  const s = String(str ?? "");
  for (let i = 0; i < s.length; i++) {
    hash = (hash * 31 + s.charCodeAt(i)) >>> 0;
  }

  return hash;
}

export function organiserColour(organiser) {
  const index = hashString(organiser) % COLOURS.length;

  return COLOURS[index];
}
