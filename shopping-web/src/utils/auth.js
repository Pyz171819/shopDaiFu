const TokenKey = "Admin-Token";

function decodeBase64Url(value = "") {
  const normalized = value.replace(/-/g, "+").replace(/_/g, "/");
  const padding = normalized.length % 4;
  const padded = padding ? normalized + "=".repeat(4 - padding) : normalized;

  return atob(padded);
}

function parseTokenPayload(token = "") {
  try {
    const segments = token.split(".");

    if (segments.length < 2) {
      return null;
    }

    return JSON.parse(decodeBase64Url(segments[1]));
  } catch (error) {
    return null;
  }
}

export function getToken() {
  return localStorage.getItem(TokenKey);
}

export function setToken(token) {
  localStorage.setItem(TokenKey, token);
}

export function removeToken() {
  localStorage.removeItem(TokenKey);
}

export function getTokenExpiresAt(token = getToken()) {
  const payload = parseTokenPayload(token);
  const exp = payload && typeof payload.exp === "number" ? payload.exp : null;

  return exp ? exp * 1000 : null;
}

export function isTokenExpired(token = getToken()) {
  if (!token) {
    return true;
  }

  const expiresAt = getTokenExpiresAt(token);

  // 如果没有过期时间，认为 token 有效（由后端判断）
  if (!expiresAt) {
    return false;
  }

  return Date.now() >= expiresAt;
}

export function getValidToken() {
  const token = getToken();

  if (isTokenExpired(token)) {
    removeToken();
    return "";
  }

  return token;
}
