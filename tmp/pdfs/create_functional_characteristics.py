"""Restrained PDF, preserving the source DOCX item order and wording."""
from pathlib import Path
from xml.sax.saxutils import escape
import re
import shutil

from docx import Document
from reportlab.lib import colors
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, KeepTogether
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output/pdf/логистРу — Функциональные характеристики.pdf'
SOURCE = ROOT / 'docs/подробное_описание_функциональных_характеристик.docx'
pdfmetrics.registerFont(TTFont('Official', 'C:/Windows/Fonts/calibri.ttf'))
pdfmetrics.registerFont(TTFont('OfficialBold', 'C:/Windows/Fonts/calibrib.ttf'))

text = '\n'.join(p.text for p in Document(SOURCE).paragraphs)
for original, revised in {
    'Включает ЭДО для файлового обмена (разделы входящих и исходящих документов).':
        'Меню ЭДО для файлового обмена (разделы входящих и исходящих документов).',
    'Изолируемый, настраиваемый доступ к разделу внутри аккаунта.':
        'Изолируемый настраиваемый доступ к разделу внутри аккаунта.',
}.items():
    assert text.count(original) == 1, f'Source phrase changed: {original}'
    text = text.replace(original, revised)
groups = [[line.strip() for line in block.splitlines() if line.strip()]
          for block in re.split(r'\n\s*\n', text) if block.strip()]
title, *intro = groups[0]
items = []
note = None
for group in groups[1:]:
    if group[0] == 'адрес сайта':
        break  # Unfilled contact placeholders are replaced by the existing platform contacts.
    if group[0].startswith('Дополнительные функции'):
        note = 'Дополнительные функции ' + ' '.join(group[1:]).replace('Подключаются', 'подключаются', 1)
        continue
    # Some DOCX paragraph boundaries separate adjacent items without an empty line.
    boundaries = ['Документооборот', 'Актуальные Справочники из Государственных реестров']
    current = []
    for line in group:
        if line in boundaries and current:
            items.append((current[0], current[1:], note))
            note = None
            current = []
        current.append(line)
    items.append((current[0], current[1:], note))
    note = None

assert len(items) == 15, f'Unexpected source structure: {len(items)} items'
WIDTH, HEIGHT = 595.276, 841.89
MARGIN = 51
CONTENT = WIDTH - 2 * MARGIN
INK = colors.HexColor('#222222')
BLUE = colors.HexColor('#1264EF')
body = ParagraphStyle('body', fontName='Official', fontSize=10.5, leading=14, textColor=INK, spaceAfter=3)
heading = ParagraphStyle('item', parent=body, fontSize=11, leading=14, spaceAfter=0)
title_style = ParagraphStyle('title', parent=body, fontName='OfficialBold', fontSize=19, leading=23, spaceAfter=13)
logo = ImageReader(ROOT / 'resources/documents/logo.png')
logo_w, logo_h = logo.getSize()


def paragraph(value, style=body):
    return Paragraph(escape(value), style)


def item_heading(number, name):
    table = Table([[paragraph(f'{number}.', heading), paragraph(name, heading)]],
                  colWidths=[30, CONTENT - 42], hAlign='LEFT')
    table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('LEFTPADDING', (0, 0), (-1, -1), 0),
        ('LEFTPADDING', (0, 0), (0, 0), 8),
        ('LINEBEFORE', (0, 0), (0, 0), 2.2, BLUE),
        ('RIGHTPADDING', (0, 0), (-1, -1), 0),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ]))
    return table


def page_decor(c, doc):
    c.saveState()
    c.drawImage(logo, MARGIN, HEIGHT - 70, width=180, height=180 * logo_h / logo_w, mask='auto')
    c.setFillColor(colors.HexColor('#777777'))
    c.setFont('Official', 8)
    c.drawString(MARGIN, 29, '24logist.ru   |   info@24logist.ru   |   +7 (495) 109-25-44')
    c.drawRightString(WIDTH - MARGIN, 29, str(doc.page))
    c.restoreState()


def build_functional():
    story = [paragraph(title, title_style)]
    story.extend(paragraph(line) for line in intro)
    story.append(Spacer(1, 8))
    for number, (name, descriptions, preceding_note) in enumerate(items, 1):
        block = []
        if preceding_note:
            block += [paragraph(preceding_note), Spacer(1, 5)]
        block += [item_heading(number, name), Spacer(1, 5)]
        block += [paragraph(line) for line in descriptions]
        block.append(Spacer(1, 9))
        story.append(KeepTogether(block))
    SimpleDocTemplate(str(OUT), pagesize=(WIDTH, HEIGHT), leftMargin=MARGIN, rightMargin=MARGIN,
                      topMargin=76, bottomMargin=51,
                      title=title, author='ООО «Энерви Групп»', pageCompression=1).build(
                          story, onFirstPage=page_decor, onLaterPages=page_decor)
    shutil.copyfile(OUT, ROOT / 'resources/documents/functional-characteristics.pdf')
    reader = PdfReader(OUT)
    extracted = '\n'.join(page.extract_text() for page in reader.pages)
    normalized = re.sub(r'\s+', ' ', extracted)
    cursor = 0
    for name, descriptions, preceding_note in items:
        for part in ([preceding_note] if preceding_note else []) + [name] + descriptions:
            needle = re.sub(r'\s+', ' ', part)
            cursor = normalized.index(needle, cursor) + len(needle)
    print(f'Generated {len(reader.pages)} pages; verified all 15 items against the DOCX in source order.')
    return {'file': str(OUT), 'pages': len(reader.pages), 'bytes': OUT.stat().st_size}


if __name__ == '__main__':
    build_functional()
