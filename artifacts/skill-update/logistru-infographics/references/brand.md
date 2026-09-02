# LogistRu brand rules

## Core palette

- Primary blue: `#0D67F3`
- Deep navy: `#0D2147`
- Route green: `#19BD49`
- Supporting blue: `#2A7BEE`
- Background: `#FFFFFF` or `#F7FAFF`
- Borders and faint routes: `#DCE8F8`
- Optional urgency/promo accent: `#FF5A0A`, used sparingly

Use navy for primary text, bright blue for dates, numbers, actions, and active documents, and green for confirmed or completed states. Use orange only for a real promotion, deadline, price-related message, or a deliberate comparison accent.

## Typography

- Preferred family: Geologica. Fallback: Inter, then Arial or another sans serif with full Cyrillic support.
- Use bold or extra-bold Geologica for a dominant date, number, or short headline. Use medium or semibold for labels and body copy.
- Keep a compact headline line-height, but preserve Cyrillic ascenders/descenders and visible whitespace between blocks.
- At social size, prefer fewer words and larger type. Do not solve overflow with condensed decorative fonts or tiny copy.
- Never raster-generate final Cyrillic copy when it can be typeset in SVG or HTML.

The packaged font is `assets/geologica.woff2`.

## Logo

Use `assets/logistru-logo.svg` as the source of truth.

- Maintain its aspect ratio and all original colours.
- Keep clear space around it of at least the height of the green location-pin head.
- Place it on white or a very light background.
- Do not place text, decorative routes, or cards over it.
- Prefer a confident logo size rather than a tiny footer mark. Reduce it proportionally when necessary; never crop it into a symbol-only mark.

## Visual language

- Use a white or cool-white editorial canvas with navy typography and bright-blue focal elements.
- Add restrained logistics cues: route lines, nodes, location pins, document sheets, trucks, cloud exchange, dashboards, or line icons.
- Put faint routes, document fragments, and small blue/green squares near the edges so they frame the message instead of competing with it.
- For an editorial announcement, favour one large typographic idea plus one substantial visual. Avoid a large rounded outer card, UI-like clutter, generic “confirmed” pills, and multiple unrelated footer captions.
- For a process or comparison, use thin borders, controlled shadows, and consistent icon geometry. Arrows must communicate real direction.
- Avoid stock-photo clutter, glossy 3D lettering, dense gradients, generic handshakes, and arbitrary decorative badges.

## Copy conventions

- Prefer `Заказ-заявка` with a hyphen.
- Preserve the casing `ЭТрН`.
- Use `ЭПД`, `МЧД`, `Экспедитор`, `Заказчик`, and `Перевозчик` consistently.
- Use a non-breaking space where practical between a number and unit or percent phrase.
- Do not invent a deadline, registry number, document status, or year.
