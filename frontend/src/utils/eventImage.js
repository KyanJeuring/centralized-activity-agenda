const KEYWORDS = [
  "technology",
  "business",
  "conference",
  "networking",
  "innovation",
  "startup",
  "workshop",
  "programming",
  "digital",
  "corporate",
];

function hashID(id) {
  const str = String(id ?? "default");
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = (hash * 31 + str.charCodeAt(i)) >>> 0;
  }

  return hash;
}

export function imgForEvent(eventId, imgFromApi) {
  if (imgFromApi?.trim()) return imgFromApi.trim();

  const hash = hashID(String(eventId ?? "default"));
  const keyword = KEYWORDS[hash % KEYWORDS.length];

  return `https://loremflickr.com/800/450/${keyword}?lock=${hash}`;
}
