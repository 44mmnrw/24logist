const elements = {
  server: document.querySelector('#server'),
  token: document.querySelector('#token'),
  connect: document.querySelector('#connect'),
  scenario: document.querySelector('#scenario'),
  details: document.querySelector('#details'),
  collect: document.querySelector('#collect'),
  prepare: document.querySelector('#prepare'),
  status: document.querySelector('#status'),
};

let scenarios = [];

document.addEventListener('DOMContentLoaded', async () => {
  const saved = await chrome.storage.local.get(['server', 'token']);
  elements.server.value = saved.server || 'https://24logist.ru';
  elements.token.value = saved.token || '';
  if (saved.token) await loadScenarios();
});

elements.connect.addEventListener('click', async () => {
  await chrome.storage.local.set({
    server: elements.server.value.trim(),
    token: elements.token.value.trim(),
  });
  await loadScenarios();
});

elements.scenario.addEventListener('change', renderScenario);
elements.collect.addEventListener('click', collectCurrentChat);
elements.prepare.addEventListener('click', prepareScenario);

chrome.runtime.onMessage.addListener((request) => {
  if (request?.type === 'collector:progress') setStatus(request.message);
});

async function loadScenarios(showReadyStatus = true) {
  setBusy(true);
  setStatus('Загружаю сценарии…');
  try {
    const response = await api('/community/ai/collector/scenarios');
    scenarios = response.scenarios || [];
    elements.scenario.innerHTML = '';
    for (const scenario of scenarios) {
      const option = document.createElement('option');
      option.value = String(scenario.id);
      option.textContent = `#${scenario.id} ${scenario.title}`;
      elements.scenario.append(option);
    }
    elements.scenario.disabled = scenarios.length === 0;
    renderScenario();
    if (showReadyStatus) {
      setStatus(scenarios.length ? 'Откройте нужный чат MAX и нажмите кнопку сбора.' : 'Нет доступных сценариев.');
    }
  } catch (error) {
    scenarios = [];
    elements.scenario.disabled = true;
    elements.details.hidden = true;
    setStatus(error.message, 'error');
  } finally {
    setBusy(false);
  }
}

function renderScenario() {
  const scenario = selectedScenario();
  elements.collect.disabled = !scenario;
  elements.prepare.disabled = !scenario;
  elements.details.hidden = !scenario;
  if (!scenario) return;

  const usesSourcePost = scenario.mode === 'manual';
  elements.collect.textContent = usesSourcePost ? 'Собрать обсуждение поста' : 'Собрать открытый чат';
  elements.prepare.textContent = usesSourcePost ? 'Создать тему и обсуждение' : 'Сформировать черновики';

  const sources = scenario.sources.map((source) =>
    `<li>${escapeHtml(source.name)} — собрано ${source.messages_count}</li>`
  ).join('');
  const keywords = scenario.scan_keywords.length ? scenario.scan_keywords.join(', ') : 'весь период';
  elements.details.innerHTML = `
    <strong>Тип:</strong> ${escapeHtml(scenario.mode_label)}<br>
    <strong>Период:</strong> ${formatDate(scenario.source_from)} — ${formatDate(scenario.source_to)}<br>
    <strong>Фокус:</strong> ${escapeHtml(keywords)}
    <ul>${sources}</ul>
  `;
}

async function collectCurrentChat() {
  const scenario = selectedScenario();
  if (!scenario) return;

  setBusy(true);
  setStatus('Начинаю чтение загруженной истории MAX…');
  try {
    const maxTabs = await chrome.tabs.query({ url: 'https://web.max.ru/*' });
    const tab = maxTabs.sort((left, right) => (right.lastAccessed || 0) - (left.lastAccessed || 0))[0];
    if (!tab?.url || tab.id === undefined) {
      throw new Error('Сначала откройте нужный чат в отдельной вкладке web.max.ru.');
    }
    const chatId = new URL(tab.url).pathname.match(/^\/(-?\d+)/)?.[1];
    const source = scenario.sources.find((item) => item.chat_id === chatId);
    if (!source) throw new Error('Открытый чат не выбран в этом сценарии.');

    let result;
    try {
      result = await chrome.tabs.sendMessage(tab.id, {
        type: 'collector:collect',
        sourceFrom: scenario.source_from,
        sourceTo: scenario.source_to,
      });
    } catch {
      throw new Error('Обновите вкладку MAX после установки расширения и повторите сбор.');
    }
    if (!result?.ok) throw new Error(result?.error || 'Сборщик в странице MAX не ответил.');

    let imported = 0;
    let duplicates = 0;
    const chunks = chunk(result.data.messages, 100);
    for (let index = 0; index < chunks.length; index++) {
      setStatus(`Отправляю пакет ${index + 1} из ${chunks.length}…`);
      const response = await api('/community/ai/collector/messages', 'POST', {
        scenario_id: scenario.id,
        chat_id: result.data.chat_id,
        chat_name: result.data.chat_name,
        messages: chunks[index],
      });
      imported += response.imported;
      duplicates += response.duplicates;
    }

    await loadScenarios(false);
    setStatus(`Готово: новых ${imported}, уже были ${duplicates}.`, 'success');
  } catch (error) {
    setStatus(error.message, 'error');
  } finally {
    setBusy(false);
  }
}

async function prepareScenario() {
  const scenario = selectedScenario();
  if (!scenario) return;

  setBusy(true);
  setStatus('Ставлю формирование черновиков в очередь…');
  try {
    const result = await api(`/community/ai/collector/scenarios/${scenario.id}/prepare`, 'POST', {});
    await loadScenarios(false);
    setStatus(`Сценарий запущен. Сообщений в периоде: ${result.messages_count}.`, 'success');
  } catch (error) {
    setStatus(error.message, 'error');
  } finally {
    setBusy(false);
  }
}

async function api(path, method = 'GET', body = null) {
  const response = await chrome.runtime.sendMessage({
    type: 'collector:api',
    server: elements.server.value.trim(),
    token: elements.token.value.trim(),
    path,
    method,
    body,
  });
  if (!response?.ok) throw new Error(response?.error || 'Нет ответа от сервера.');
  return response.data;
}

function selectedScenario() {
  return scenarios.find((scenario) => String(scenario.id) === elements.scenario.value) || null;
}

function setBusy(value) {
  elements.connect.disabled = value;
  elements.collect.disabled = value || !selectedScenario();
  elements.prepare.disabled = value || !selectedScenario();
}

function setStatus(message, kind = '') {
  elements.status.textContent = message;
  elements.status.className = kind;
}

function formatDate(value) {
  return new Intl.DateTimeFormat('ru-RU', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
}

function chunk(items, size) {
  const result = [];
  for (let index = 0; index < items.length; index += size) result.push(items.slice(index, index + size));
  return result;
}

function escapeHtml(value) {
  const element = document.createElement('span');
  element.textContent = String(value || '');
  return element.innerHTML;
}
