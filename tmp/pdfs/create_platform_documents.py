from pathlib import Path
from xml.sax.saxutils import escape
import json

from reportlab.pdfgen import canvas
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak,
    KeepTogether, Image as PdfImage,
)
from reportlab.lib import colors
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_LEFT
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader
from PIL import Image
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output' / 'pdf'
QA = ROOT / 'tmp' / 'pdfs' / 'platform-documents'
OUT.mkdir(parents=True, exist_ok=True)
QA.mkdir(parents=True, exist_ok=True)

for name, filename in [('Body', 'calibri.ttf'), ('Bold', 'calibrib.ttf'),
                       ('Italic', 'calibrii.ttf'), ('Light', 'calibril.ttf')]:
    pdfmetrics.registerFont(TTFont(name, str(Path('C:/Windows/Fonts') / filename)))
pdfmetrics.registerFontFamily('Body', normal='Body', bold='Bold', italic='Italic', boldItalic='Bold')

BLUE = colors.HexColor('#1264EF')
NAVY = colors.HexColor('#10244B')
GREEN = colors.HexColor('#14BA48')
INK = colors.HexColor('#202A38')
MUTED = colors.HexColor('#68768A')
LINE = colors.HexColor('#D9D9D9')
PALE = colors.HexColor('#F3F6FA')
WIDTH, HEIGHT = 595.276, 841.89
MARGIN = 48
CONTENT = WIDTH - MARGIN * 2

logo = Image.open(ROOT / 'resources/documents/logo.png').convert('RGBA')
logo.save(QA / 'logo.png')
LOGO = ImageReader(logo)

styles = {
    'eyebrow': ParagraphStyle('eyebrow', fontName='Bold', fontSize=8.3, leading=11,
                              textColor=BLUE, spaceAfter=9),
    'title': ParagraphStyle('title', fontName='Bold', fontSize=27, leading=30,
                            textColor=colors.black, spaceAfter=13),
    'lead': ParagraphStyle('lead', fontName='Body', fontSize=11.5, leading=16,
                           textColor=INK, spaceAfter=13),
    'section': ParagraphStyle('section', fontName='Bold', fontSize=14, leading=18,
                              textColor=colors.black, spaceBefore=14, spaceAfter=9,
                              keepWithNext=True),
    'sub': ParagraphStyle('sub', fontName='Bold', fontSize=11.5, leading=14.4,
                          textColor=colors.black, spaceBefore=6, spaceAfter=2,
                          keepWithNext=True),
    'body': ParagraphStyle('body', fontName='Body', fontSize=10.8, leading=14.8,
                           textColor=INK, spaceAfter=4),
    'small': ParagraphStyle('small', fontName='Body', fontSize=9.2, leading=12.4,
                            textColor=MUTED, spaceAfter=6),
    'legal': ParagraphStyle('legal', fontName='Body', fontSize=10.3, leading=14.1,
                            textColor=INK, spaceAfter=8),
    'bullet': ParagraphStyle('bullet', fontName='Body', fontSize=10.8, leading=15,
                             textColor=INK, leftIndent=12, firstLineIndent=-12,
                             spaceAfter=5),
    'cell': ParagraphStyle('cell', fontName='Body', fontSize=10.4, leading=14,
                           textColor=INK),
    'cell_header': ParagraphStyle('cell_header', fontName='Bold', fontSize=9.6,
                                  leading=13, textColor=colors.white),
    'metric': ParagraphStyle('metric', fontName='Bold', fontSize=20, leading=24,
                             textColor=NAVY),
}


def p(text, style='body'):
    return Paragraph(text, styles[style])


def title(kicker, text, intro=None):
    result = [p(kicker.upper(), 'eyebrow'), p(text, 'title')]
    if intro:
        result.append(p(intro, 'lead'))
    return result


def section(number, text):
    return p(f'<font color="#1264EF">{number}</font>  {text}', 'section')


def block(name, text):
    return KeepTogether([p(name, 'sub'), p(text)])


def bullet(text):
    return p(f'<font color="#1264EF">•</font>  {text}', 'bullet')


def data_table(rows, widths, header=True):
    cells = [[p(str(cell), 'cell_header' if header and i == 0 else 'cell')
              for cell in row] for i, row in enumerate(rows)]
    table = Table(cells, colWidths=widths, hAlign='LEFT', repeatRows=1 if header else 0)
    commands = [('GRID', (0, 0), (-1, -1), .5, LINE),
                ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
                ('LEFTPADDING', (0, 0), (-1, -1), 12),
                ('RIGHTPADDING', (0, 0), (-1, -1), 12),
                ('TOPPADDING', (0, 0), (-1, -1), 7),
                ('BOTTOMPADDING', (0, 0), (-1, -1), 7)]
    if header:
        commands += [('BACKGROUND', (0, 0), (-1, 0), NAVY)]
        for i in range(1, len(rows)):
            commands += [('BACKGROUND', (0, i), (-1, i), colors.white if i % 2 else PALE)]
    table.setStyle(TableStyle(commands))
    return table


def metric_table():
    table = Table([
        [p('1', 'metric'), p('до 3', 'metric'), p('300 МБ', 'metric')],
        [p('личный кабинет', 'small'), p('юридических лиц', 'small'), p('для скан-копий на аккаунт', 'small')],
    ], colWidths=[CONTENT / 3] * 3, hAlign='LEFT')
    table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), PALE),
        ('BOX', (0, 0), (-1, -1), .5, LINE),
        ('LINEAFTER', (0, 0), (1, -1), .5, LINE),
        ('LEFTPADDING', (0, 0), (-1, -1), 13),
        ('TOPPADDING', (0, 0), (-1, 0), 9),
        ('BOTTOMPADDING', (0, 0), (-1, 0), 0),
        ('BOTTOMPADDING', (0, 1), (-1, 1), 6),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ]))
    return table


class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, document_label='', **kwargs):
        super().__init__(*args, **kwargs)
        self.saved_pages = []
        self.document_label = document_label

    def showPage(self):
        self.saved_pages.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        total = len(self.saved_pages)
        for state in self.saved_pages:
            self.__dict__.update(state)
            self.setFont('Body', 8.2)
            self.setFillColor(MUTED)
            self.drawRightString(WIDTH - MARGIN, 27, f'{self._pageNumber} / {total}')
            super().showPage()
        super().save()


def decor(label):
    def paint(c, doc):
        c.saveState()
        c.setFillColor(BLUE)
        c.rect(0, HEIGHT - 5, WIDTH * .84, 5, stroke=0, fill=1)
        c.setFillColor(GREEN)
        c.rect(WIDTH * .84, HEIGHT - 5, WIDTH * .16, 5, stroke=0, fill=1)
        logo_width = 135
        logo_height = logo_width * logo.height / logo.width
        c.drawImage(LOGO, MARGIN, HEIGHT - 65, width=logo_width, height=logo_height, mask='auto')
        c.setFont('Body', 8.5)
        c.setFillColor(MUTED)
        c.drawRightString(WIDTH - MARGIN, HEIGHT - 46, 'ОБЛАЧНАЯ TMS-ПЛАТФОРМА')
        c.drawRightString(WIDTH - MARGIN, HEIGHT - 59, '24logist.ru')
        c.linkURL('https://24logist.ru', (WIDTH-130, HEIGHT-63, WIDTH-MARGIN, HEIGHT-47), relative=0)
        c.setStrokeColor(LINE)
        c.setLineWidth(.5)
        c.line(MARGIN, 48, WIDTH - MARGIN, 48)
        c.setFont('Body', 8.2)
        c.drawString(MARGIN, 33, '24logist.ru   |   info@24logist.ru   |   +7 (495) 109-25-44')
        c.setFont('Body', 7.4)
        c.drawString(MARGIN, 20, label)
        c.restoreState()
    return paint


def build(filename, label, pages):
    story = []
    for i, items in enumerate(pages):
        if i:
            story.append(PageBreak())
        story.extend(items)
    path = OUT / filename
    doc = SimpleDocTemplate(str(path), pagesize=(WIDTH, HEIGHT),
                            leftMargin=MARGIN - 6, rightMargin=MARGIN - 6,
                            topMargin=89, bottomMargin=63,
                            title=label, author='ООО «Энерви Групп»',
                            subject='Платформа логистРу',
                            pageCompression=1)
    doc.build(story, onFirstPage=decor(label), onLaterPages=decor(label),
              canvasmaker=NumberedCanvas)
    reader = PdfReader(path)
    text = '\n\n'.join(page.extract_text() for page in reader.pages)
    (QA / (path.stem + '.txt')).write_text(text, encoding='utf-8')
    return {'file': str(path), 'pages': len(reader.pages), 'bytes': path.stat().st_size,
            'characters': len(text)}


functional_1 = title('Описание платформы', 'Функциональные<br/>характеристики',
    'логистРу объединяет работу с заявками, контрагентами, финансами и перевозочными документами в личном кабинете экспедиторской компании.')
functional_1 += [metric_table(), section('01', 'Заявки и финансовый учёт')]
functional_1 += [
    block('Справочники заявок и контрагентов',
          'Круглосуточный доступ к справочникам с разных устройств. Для каждого менеджера настраивается изолированный доступ к заявкам и контрагентам.'),
    block('Автозаполнение контрагентов',
          'Карточки контрагентов заполняются автоматически при оформлении карточки заявки и на основе входящих поручений экспедитору.'),
    block('Калькулятор маршрута',
          'Предварительный расчёт стоимости доставки и времени в пути с учётом платных участков дорог и рабочего времени водителя.'),
    block('Финансовый учёт',
          'Загрузка банковских выписок и добавление платежей вручную. На основе этих данных система распределяет платежи по заявкам и статьям доходов и расходов. Управленческий учёт позволяет анализировать денежный поток и формировать отчёты по заполненным статьям.'),
    block('Контроль оплат и документооборот',
          'Планирование дат оплаты, учёт оплаченных заявок и заявок, ожидающих оплаты. Раздел связан с модулями «Счета на оплату» и «Финансы».'),
    section('02', 'Электронные перевозочные документы'),
    block('Работа с ЭПД',
          'Входящие, исходящие и сформированные электронные перевозочные документы хранятся в одном разделе. Настройки позволяют распределять входящие документы между аккаунтами внутри организации. Поддерживаются XML-файлы для обмена электронными поручениями экспедитору (ЭПЭ) и электронными транспортными накладными (ЭТрН).'),
    block('Корзина черновиков',
          'Отменённые документы и документы с ошибками можно скрыть из рабочего списка в разделе «Корзина».'),
]

functional_2 = title('Описание платформы', 'Документы и дополнительные<br/>возможности')
functional_2 += [
    block('Архив и резервные копии ЭПД',
          'Круглосуточный доступ к архиву и выделенное облачное пространство для резервных копий документов. Условия и сроки хранения определяются договором с учётом применимых требований законодательства, включая изменения, внесённые Федеральным законом от 07.06.2025 № 140-ФЗ.'),
    block('Бумажные перевозочные документы',
          'Формирование договора-заявки с перевозчиком, договора на оказание транспортно-экспедиционных услуг с заказчиком, транспортных накладных и доверенностей на получение груза водителем.'),
    block('Хранение скан-копий',
          'Изолированное пространство на сервере для паспортов, водительских удостоверений, подписанных договоров и других сканированных документов. Базовый объём составляет 300 МБ на аккаунт; загрузка выполняется в поддерживаемых сервисом форматах.'),
    block('Интеграция с ООО «Эвотор ОФД»',
          'Работа с оператором ЭДО и ИС ЭПД через платформу: обмен документами, передача данных в ГИС ЭПД, роуминг, хранение и защита данных в рамках подключённых услуг оператора.'),
    section('03', 'Контрагенты и государственные реестры'),
    block('Проверка контрагентов',
          'Рейтинг перевозчика АТИ, оценка надёжности, ключевые сведения и финансовая отчётность выбранного контрагента.'),
    block('Справочники',
          'Сведения из реестра экспедиторов, Государственного адресного реестра (ГАР) и ФИАС, а также реестра машиночитаемых доверенностей (МЧД).'),
    section('04', 'Дополнительные функции'),
    p('Подключаются на весь аккаунт компании независимо от количества личных кабинетов внутри него.'),
    block('Нормализация адресов по ФИАС',
          'Приведение адреса к данным государственного адресного реестра и использование соответствующего кода ФИАС помогают уменьшить количество ошибок.'),
    block('Счета на оплату и бухгалтерские документы',
          'Создание счетов и предусмотренных сервисом форм бухгалтерских документов, учёт платежей внутри кабинета. Раздел включает файловый обмен через ЭДО, входящие и исходящие документы, а также отдельные настройки доступа.'),
    p('<b>Для руководителя и бухгалтера</b> предусмотрен бесплатный доступ к разделу «Счета на оплату».'),
]

offer_1 = title('Для экспедиторских компаний', 'Коммерческое<br/>предложение')
offer_1 += [
    p('<b>От</b>  ООО «Энерви Групп»<br/>ИНН 5074081476 · КПП 507401001', 'small'),
    p('<b>Кому</b>  ________________________________________________<br/>ИНН / КПП  ___________________________________________<br/>№ ____________   от ____________________', 'body'),
    Spacer(1, 7),
    p('Предлагаем Вашей компании доступ к облачной TMS-платформе <b>логистРу</b> для учёта перевозок, работы с перевозочными документами и электронного взаимодействия с участниками перевозки.', 'lead'),
    p('Система разработана для экспедиторских компаний. Единый интерфейс помогает организовать работу менеджеров, контролировать оплаты и сократить повторный ввод данных.'),
    section('01', 'Тестовый период 14 дней'),
    p('Оцените возможности платформы на рабочих задачах Вашей компании. В тестовый период доступны базовые разделы и инструменты:'),
    bullet('Калькулятор маршрута.'),
    bullet('Банковские выписки и финансовые отчёты.'),
    bullet('Модули для работы с ЭПД и бумажными документами.'),
    bullet('Интеграция с оператором ЭДО / ИС ЭПД ООО «Эвотор ОФД».'),
    bullet('Справочники государственных реестров и проверка контрагентов.'),
    bullet('Изолированный настраиваемый доступ для каждого менеджера.'),
    bullet('Хранение резервных копий ЭПД.'),
    bullet('300 МБ на аккаунт для хранения скан-копий документов.'),
    section('02', 'Дополнительные разделы'),
    bullet('<b>Счета на оплату</b> и предусмотренные сервисом формы бухгалтерских документов.'),
    bullet('<b>Нормализация адресов по ФИАС.</b>'),
    p('Состав подключаемых дополнительных функций и условия их использования согласовываются отдельно.', 'small'),
]

offer_2 = title('Коммерческое предложение', 'Стоимость и подключение')
offer_2 += [
    p('После тестового периода стоимость доступа определяется выбранным тарифом и количеством сотрудников. Индивидуальные условия фиксируются ниже и в договоре.'),
    data_table([
        ['Параметр', 'Индивидуальные условия'],
        ['Тарифный план', '________________________________'],
        ['Количество сотрудников', '__________'],
        ['Стоимость доступа', '________________ руб. / месяц'],
        ['НДС', '________________________________'],
        ['Дополнительные функции', '________________________________'],
        ['Обмен ЭДО / ЭПД', 'Объём и стоимость: __________________'],
        ['Срок действия предложения', 'До ______________________________'],
    ], [190, CONTENT - 190]),
    section('03', 'Правовые условия'),
    p('<b>Доступ к программе.</b> Право использования платформы, состав функций, порядок оплаты, условия поддержки и хранения данных определяются договором и применимыми условиями использования. Исключительное право на программу пользователю не передаётся.', 'legal'),
    p('<b>Электронный документооборот.</b> Обмен через ООО «Эвотор ОФД», передача данных в ГИС ЭПД и роуминг осуществляются в рамках подключённых услуг оператора. Состав и стоимость этих услуг согласовываются при подключении.', 'legal'),
    p('<b>Персональные данные.</b> Обработка персональных данных осуществляется в соответствии с Федеральным законом от 27.07.2006 № 152-ФЗ и опубликованной политикой обработки персональных данных. Порядок обработки данных, распределение обязанностей сторон и меры защиты определяются законодательством и договором.', 'legal'),
    p('<b>Российское программное обеспечение.</b> Реестровая запись № 34983 от 31.08.2026 в Едином реестре российских программ для ЭВМ и баз данных.', 'legal'),
    p('Настоящий документ предназначен для обсуждения условий сотрудничества и не является офертой, в том числе публичной, в смысле статей 435 и 437 ГК РФ. Обязательства сторон возникают после заключения договора в согласованной форме.', 'small'),
    Spacer(1, 8),
    p('<b>Для подключения тестового доступа</b><br/>info@24logist.ru · +7 (495) 109-25-44'),
    Table([[
        p('Директор по развитию сервиса<br/>Станислав Аристов /', 'small'),
        PdfImage(str(ROOT / 'resources/documents/signature-aristov.png'), width=96, height=32),
    ]], colWidths=[145, 105], hAlign='LEFT', style=TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('LEFTPADDING', (0, 0), (-1, -1), 0),
        ('RIGHTPADDING', (0, 0), (-1, -1), 0),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ])),
]


results = [
    build('логистРу — Функциональные характеристики.pdf',
          'логистРу · Функциональные характеристики', [functional_1, functional_2]),
    build('логистРу — Коммерческое предложение.pdf',
          'логистРу · Коммерческое предложение', [offer_1, offer_2]),
]
(QA / 'manifest.json').write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding='utf-8')
print(json.dumps(results, ensure_ascii=False, indent=2))
