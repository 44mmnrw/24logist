const MONTHS = {
  января: 0, январь: 0, янв: 0,
  февраля: 1, февраль: 1, фев: 1,
  марта: 2, март: 2, мар: 2,
  апреля: 3, апрель: 3, апр: 3,
  мая: 4, май: 4,
  июня: 5, июнь: 5, июн: 5,
  июля: 6, июль: 6, июл: 6,
  августа: 7, август: 7, авг: 7,
  сентября: 8, сентябрь: 8, сен: 8, сент: 8,
  октября: 9, октябрь: 9, окт: 9,
  ноября: 10, ноябрь: 10, ноя: 10,
  декабря: 11, декабрь: 11, дек: 11,
};

chrome.runtime.onMessage.addListener((request, _sender, sendResponse) => {
  if (request?.type !== 'collector:collect') {
    return false;
  }

  collectChat(request)
    .then((data) => sendResponse({ ok: true, data }))
    .catch((error) => sendResponse({ ok: false, error: error.message }));

  return true;
});

async function collectChat({ sourceFrom, sourceTo }) {
  const chatId = location.pathname.match(/^\/(-?\d+)/)?.[1];
  if (!chatId) {
    throw new Error('Откройте групповой чат MAX, указанный в сценарии.');
  }

  const main = document.querySelector('main');
  const scroller = main?.querySelector('.scrollListScrollable');
  if (!main || !scroller) {
    throw new Error('Не удалось найти историю чата. Закройте панель информации и повторите.');
  }

  const from = new Date(sourceFrom);
  const to = new Date(sourceTo);
  if (Number.isNaN(from.getTime()) || Number.isNaN(to.getTime())) {
    throw new Error('В сценарии задан некорректный период.');
  }

  const collected = new Map();
  let stagnantPasses = 0;
  let previousOldest = Number.POSITIVE_INFINITY;

  for (let pass = 0; pass < 120; pass++) {
    const batch = extractLoadedMessages(main, from, to);
    for (const message of batch.messages) {
      collected.set(message.local_key, message);
    }

    notifyProgress(`Загружено из чата: ${collected.size}. Просматриваю историю…`);

    if (batch.oldestTimestamp !== null && batch.oldestTimestamp <= from.getTime()) {
      break;
    }

    if (collected.size >= 5000) {
      break;
    }

    if (batch.oldestTimestamp === previousOldest) {
      stagnantPasses++;
    } else {
      stagnantPasses = 0;
      previousOldest = batch.oldestTimestamp;
    }

    if (stagnantPasses >= 4) {
      break;
    }

    const beforeHeight = scroller.scrollHeight;
    scroller.scrollTop = 0;
    scroller.dispatchEvent(new Event('scroll', { bubbles: true }));
    await wait(900);

    if (scroller.scrollHeight === beforeHeight && scroller.scrollTop === 0) {
      stagnantPasses++;
    }
  }

  const messages = [];
  for (const message of collected.values()) {
    const senderKey = await sha256(`${chatId}|${message.sender}`);
    const text = redact(message.text);
    if (!text) continue;

    messages.push({
      external_id: await sha256(`${chatId}|${message.sent_at}|${senderKey}|${text}`),
      sender_key: senderKey,
      text,
      sent_at: message.sent_at,
    });
  }

  messages.sort((a, b) => a.sent_at.localeCompare(b.sent_at));
  if (messages.length === 0) {
    throw new Error('В загруженной истории нет текстовых сообщений за период сценария.');
  }

  return {
    chat_id: chatId,
    chat_name: getChatName(main),
    messages: messages.slice(0, 5000),
  };
}

function extractLoadedMessages(main, from, to) {
  const items = Array.from(main.querySelectorAll('[role="listitem"]'));
  const messages = [];
  let currentDate = null;
  let lastSender = 'Участник';
  let oldestTimestamp = null;

  for (const item of items) {
    const container = item.closest('.item') || item.parentElement;
    const separator = container?.querySelector(':scope > .capsuleSeparator');
    if (separator) {
      currentDate = parseRussianDate(separator.textContent, to);
    }

    const bubble = item.querySelector('.bubbleContent');
    if (!bubble || !currentDate) continue;

    const variant = bubble.closest('[data-bubbles-variant]')?.getAttribute('data-bubbles-variant');
    const author = bubble.querySelector(':scope > .header button .name .text')?.textContent?.trim();
    if (variant === 'outgoing') {
      lastSender = 'Авторизованный пользователь';
    } else if (author) {
      lastSender = author;
    }

    const textElement = Array.from(bubble.children)
      .find((element) => element.matches('span.text'));
    const text = textElement?.innerText?.trim();
    const timeText = bubble.querySelector(':scope > .meta .text')?.textContent || '';
    const time = timeText.match(/(\d{1,2}):(\d{2})/);
    if (!text || !time) continue;

    const sentAt = new Date(currentDate);
    sentAt.setHours(Number(time[1]), Number(time[2]), 0, 0);
    const timestamp = sentAt.getTime();
    oldestTimestamp = oldestTimestamp === null ? timestamp : Math.min(oldestTimestamp, timestamp);

    if (timestamp < from.getTime() || timestamp > to.getTime()) continue;

    messages.push({
      local_key: `${timestamp}|${lastSender}|${text}`,
      sender: lastSender,
      text,
      sent_at: sentAt.toISOString(),
    });
  }

  return { messages, oldestTimestamp };
}

function parseRussianDate(value, referenceDate) {
  const normalized = String(value || '')
    .toLocaleLowerCase('ru-RU')
    .replace(/[.,]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
  const today = new Date();

  if (normalized === 'сегодня') return startOfDay(today);
  if (normalized === 'вчера') {
    const date = startOfDay(today);
    date.setDate(date.getDate() - 1);
    return date;
  }

  const match = normalized.match(/^(\d{1,2})\s+([а-яё]+)(?:\s+(\d{4}))?$/u);
  if (!match || MONTHS[match[2]] === undefined) return null;

  let year = match[3] ? Number(match[3]) : referenceDate.getFullYear();
  let date = new Date(year, MONTHS[match[2]], Number(match[1]));
  if (!match[3] && date.getTime() > referenceDate.getTime() + 31 * 86400000) {
    date = new Date(year - 1, MONTHS[match[2]], Number(match[1]));
  }
  return startOfDay(date);
}

function startOfDay(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function redact(value) {
  return String(value || '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/\b[\w.%+\-]+@[\w.\-]+\.[A-Z]{2,}\b/giu, '[email]')
    .replace(/(?<!\d)(?:\+?7|8)[\s()\-]*\d{3}[\s()\-]*\d{3}[\s\-]*\d{2}[\s\-]*\d{2}(?!\d)/gu, '[телефон]')
    .replace(/https?:\/\/\S+/giu, '[ссылка]')
    .replace(/\B@[A-Za-zА-Яа-яЁё0-9_]{3,}/gu, '[пользователь]')
    .replace(/\b[АВЕКМНОРСТУХABEKMHOPCTYX]\d{3}[АВЕКМНОРСТУХABEKMHOPCTYX]{2}\s?\d{2,3}\b/giu, '[госномер]')
    .trim();
}

function getChatName(main) {
  const heading = main.querySelector('h2');
  return (heading?.textContent || 'Чат MAX').replace(/^Окно чата с\s+/i, '').trim();
}

async function sha256(value) {
  const bytes = new TextEncoder().encode(value);
  const digest = await crypto.subtle.digest('SHA-256', bytes);
  return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
}

function notifyProgress(message) {
  chrome.runtime.sendMessage({ type: 'collector:progress', message }).catch(() => {});
}

function wait(milliseconds) {
  return new Promise((resolve) => setTimeout(resolve, milliseconds));
}
