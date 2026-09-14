from pathlib import Path
from html import escape
from html.parser import HTMLParser
import re

root = Path(__file__).resolve().parents[1]
source = Path(r'C:\Users\rozov\.codex\attachments\6bcabdcd-8d3a-4297-a75a-2df7fb9aac30\pasted-text.txt')
lines = [line.strip() for line in source.read_text(encoding='utf-8-sig').splitlines() if line.strip()]
title, body_lines = lines[0], lines[1:]
h2 = {
    'Кто подает заявку на получение МЧД?',
    'Куда подавать заявку на выпуск МЧД: налоговая, оператор ЭДО или Госуслуги?',
    'Можно ли установить МЧД на телефон и подписывать документы за юрлицо?',
    'Срок действия МЧД',
    'Ограничение действий: можно ли дать право только на ТН или заявки?',
    'Как установить МЧД на компьютер',
    'Немного о сертификате КЭП сотрудника.',
}
h3 = {'Что в нём содержится', 'Зачем нужен сотруднику', 'Важное условие: машиночитаемая доверенность (МЧД)', 'Где и как получают', 'Правовая основа'}

def inline(text):
    # Preserve all original wording; apply emphasis only to existing labels.
    value = escape(text, quote=False)
    for label in ['Сайт ФНС (service.nalog.ru/dovel):', 'Оператор ЭДО (например, Контур, СБИС, ЭВОТОР):', 'Портал «Госуслуги»', 'Учётные системы (например, 1С)', 'Загрузить файл МЧД.', 'Найти МЧД автоматически.', 'Найдите в интерфейсе системы раздел для МЧД.', 'Выберите способ добавления:', 'Свяжите МЧД с личной КЭП сотрудника.', 'Проверьте работоспособность.']:
        if text.startswith(label):
            value = '<strong>' + escape(label, quote=False) + '</strong>' + escape(text[len(label):], quote=False)
            break
    return value

parts = []
i = 0
while i < len(body_lines):
    line = body_lines[i]
    if i == 0:
        parts.append('<div class="lead"><p>' + inline(line) + '</p></div>')
    elif line in h2:
        parts.append('<h2>' + inline(line) + '</h2>')
    elif line in h3:
        parts.append('<h3>' + inline(line) + '</h3>')
    elif re.match(r'^\d+\.\s', line):
        parts.append('<ol>')
        while i < len(body_lines) and re.match(r'^\d+\.\s', body_lines[i]):
            parts.append('  <li><p>' + inline(re.sub(r'^\d+\.\s+', '', body_lines[i])) + '</p>')
            i += 1
            if i < len(body_lines) and re.match(r'^[·•]\s', body_lines[i]):
                parts.append('    <ul>')
                while i < len(body_lines) and re.match(r'^[·•]\s', body_lines[i]):
                    parts.append('      <li><p>' + inline(re.sub(r'^[·•]\s+', '', body_lines[i])) + '</p></li>')
                    i += 1
                parts.append('    </ul>')
            parts.append('  </li>')
        parts.append('</ol>')
        continue
    elif re.match(r'^[·•]\s', line):
        parts.append('<ul>')
        while i < len(body_lines) and re.match(r'^[·•]\s', body_lines[i]):
            parts.append('  <li><p>' + inline(re.sub(r'^[·•]\s+', '', body_lines[i])) + '</p></li>')
            i += 1
        parts.append('</ul>')
        continue
    elif line.startswith('Сама по себе МЧД —') or line.startswith('Чтобы сотрудник мог подписывать документы'):
        parts.append('<blockquote><p>' + inline(line) + '</p></blockquote>')
    else:
        parts.append('<p>' + inline(line) + '</p>')
    i += 1

fragment = '\n\n'.join(parts) + '\n'
out = root / 'output' / 'copy'
out.mkdir(parents=True, exist_ok=True)
(out / 'mchd-24logist-body.html').write_text(fragment, encoding='utf-8')
(out / 'mchd-24logist-title.txt').write_text(title + '\n', encoding='utf-8')

css = (root / 'resources/css/landing.css').read_text(encoding='utf-8-sig')
article_css = css[css.index('.blog-post-body {'):css.index('.blog-tags {')]
preview = '''<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>''' + escape(title) + '''</title><style>
* { box-sizing: border-box; }
body { margin: 0; background: #f1f5f9; color: #22384d; }
main { max-width: 980px; margin: 36px auto; padding: 0 18px; }
h1 { font: 700 36px/1.2 Arial, sans-serif; margin: 28px 0; }
.instructions { padding: 18px 22px; background: white; border: 1px solid #d6e0e5; border-radius: 10px; font: 15px/1.5 Arial, sans-serif; }
button { background: #22384d; color: white; border: 0; border-radius: 5px; padding: 10px 16px; cursor: pointer; font: inherit; }
''' + article_css + '''
@media(max-width:640px) { main { margin: 16px auto; } h1 { font-size:28px; } .blog-post-body { padding:20px; } .blog-post-body--article h2 { font-size:24px; } }
</style></head><body><main>
<div class="instructions"><p>Название ниже перенесите в поле «Название». Для поля «Текст статьи» выделите оформленный текст кнопкой, скопируйте его и вставьте в редактор с сохранением форматирования.</p>
<button type="button" onclick="const r=document.createRange();r.selectNodeContents(document.getElementById('article-body'));const s=window.getSelection();s.removeAllRanges();s.addRange(r);">Выделить текст статьи</button></div>
<h1>''' + escape(title) + '''</h1>
<article id="article-body" class="blog-post-body blog-post-body--article">
''' + fragment + '''</article></main></body></html>'''
(out / 'mchd-24logist-preview.html').write_text(preview, encoding='utf-8')

class Check(HTMLParser):
    def __init__(self):
        super().__init__()
        self.text = []
        self.stack = []
        self.counts = {}
    def handle_starttag(self, tag, attrs):
        self.stack.append(tag)
        self.counts[tag] = self.counts.get(tag, 0) + 1
        if tag == 'li':
            assert self.stack[-2] in ('ul', 'ol')
    def handle_endtag(self, tag):
        assert self.stack.pop() == tag, tag
    def handle_data(self, data):
        self.text.append(data)

check = Check()
check.feed(fragment)
assert not check.stack
normalize = lambda value: re.sub(r'\s+', ' ', value).strip()
expected = ' '.join(re.sub(r'^(?:[·•]|\d+\.)\s+', '', line) for line in body_lines)
assert normalize(''.join(check.text)) == normalize(expected), 'Original text changed'
assert check.counts.get('h2') == len(h2)
assert check.counts.get('h3') == len(h3)
assert not check.counts.get('h1')
print('Verified: original wording and order preserved; valid HTML nesting; 7 H2, 5 H3, 2 callouts, lead and nested lists.')
print(out / 'mchd-24logist-preview.html')
print(out / 'mchd-24logist-body.html')
