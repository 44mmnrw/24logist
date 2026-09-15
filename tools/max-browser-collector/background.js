const ALLOWED_SERVERS = new Set([
  'https://24logist.ru',
  'https://24logistru.test',
]);

chrome.runtime.onMessage.addListener((request, _sender, sendResponse) => {
  if (request?.type !== 'collector:api') {
    return false;
  }

  callApi(request)
    .then((data) => sendResponse({ ok: true, data }))
    .catch((error) => sendResponse({ ok: false, error: error.message }));

  return true;
});

async function callApi({ server, token, path, method = 'GET', body = null }) {
  const origin = normalizeServer(server);
  if (!ALLOWED_SERVERS.has(origin)) {
    throw new Error('Разрешён только сервер 24logist.ru.');
  }
  if (typeof token !== 'string' || token.length < 32) {
    throw new Error('Укажите токен сборщика длиной не менее 32 символов.');
  }

  const response = await fetch(origin + path, {
    method,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...(body === null ? {} : { 'Content-Type': 'application/json' }),
    },
    body: body === null ? null : JSON.stringify(body),
  });

  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const message = payload.message
      || Object.values(payload.errors || {}).flat()[0]
      || `Сервер ответил HTTP ${response.status}.`;
    throw new Error(message);
  }

  return payload;
}

function normalizeServer(value) {
  try {
    return new URL(String(value || '').trim()).origin;
  } catch {
    throw new Error('Некорректный адрес сервера.');
  }
}
