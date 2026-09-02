---
name: logistru-infographics
description: Create or revise branded LogistRu infographics from Russian briefs and articles for the blog, Telegram, MAX, and promotional campaigns. Use when exact Cyrillic copy, platform-aware composition, logistics diagrams, or LogistRu visual identity matter; do not use for unrelated generic image generation.
---

# LogistRu Infographics

Create publication-ready LogistRu visuals with exact Russian text, a clear editorial hierarchy, and an undistorted official logo.

## Prepare the brief

1. Extract a compact fact sheet from the user's latest message: headline, supporting message, actors, steps, document names, dates, numbers, CTA, URL, destination, and requested background treatment.
2. Treat the latest correction as authoritative. Do not restore an older phrase, year, role, or document relationship.
3. Fix obvious spelling and punctuation, but do not invent registry details, dates, legal status, prices, deadlines, or calls to action.
4. Read [references/brand.md](references/brand.md) and [references/visual-style.md](references/visual-style.md). If the destination or layout type is known, also read [references/formats.md](references/formats.md).

## Choose the composition

- Use an editorial hero for an announcement, release, date, offer, or one-status message: one dominant headline or number, one supporting sentence, and one logistics visual.
- Use a connected flow for a process or document lifecycle.
- Use equal columns for genuinely parallel scenarios, roles, or options. Give each column one semantic accent and a shared header/footer only when they add information.
- Do not turn a simple announcement into a dashboard of small cards, badges, and footer labels. Remove secondary copy before shrinking important text.

## Produce the visual

- Typeset all final Russian text programmatically in SVG or HTML/CSS. Use image generation only for wordless illustrations, textures, or backgrounds.
- Use `assets/logistru-logo.svg` unchanged. Never redraw, crop, recolour, blur, or ask an image model to reproduce it.
- Use the packaged `assets/geologica.woff2` and wait for it to load before measuring or rendering text.
- Make a delivered SVG self-contained when practical: embed the packaged font and inline or data-embed the unchanged official logo. Otherwise deliver the SVG together with its required assets and make the PNG the primary artifact.
- Keep the canvas edge-to-edge. A plain background means solid white or the requested colour, not a rounded outer card, grey side fields, or letterboxing. Transparency requires an explicit request.
- Keep decoration subordinate and mostly near the edges. Decorative routes or arrows must not imply a false process.

## Fit text from measured geometry

Never size text containers by eye.

1. Reserve explicit boxes for the logo, headline, visual, supporting copy, badges, and CTA within the platform safe zone.
2. Load the final font, measure every text bounding box, then size its container. In SVG use actual rendered bounds such as `getBBox()`; in HTML/CSS use intrinsic sizing and inspect the resulting layout.
3. For a capsule or badge, calculate width from icon width + gap + measured text width + left/right padding. Keep at least 24 px horizontal padding after the visible content at a 1280 px canvas, and add a 10% guard when renderer metrics are uncertain.
4. Wrap headlines manually at semantic boundaries. Keep a clear gap between the headline box and illustration; never rely on overlap or clipping to make a line fit.
5. Run an overflow check after rendering: every text and logo bounding box must remain inside both its container and the destination safe zone. Pay special attention to the right edge of capsules and the last word of each headline line.

If a supplemental badge repeats the headline or needs disproportionate space, omit it. A decorative status pill such as “Подтверждено” is optional, not a default requirement.

## Accuracy and visual QA

Before delivery, render the exact final artifact and inspect it:

- at original size for clipping, spacing, logo fidelity, and icon quality;
- at a 320 px-wide chat-feed preview for headline hierarchy and legibility;
- against the fact sheet, character by character, for years, dates, numbers, URLs, `ЭПД`, `ЭТрН`, `МЧД`, `Заказ-заявка`, and `Экспедиторская расписка`.

Reject and correct garbled Cyrillic, invented words, duplicated letters, clipped text, under-padded badges, warped icons, changed logos, unreadable small copy, or a background treatment the user asked to remove. Visual appeal does not compensate for inaccurate or overflowing text.

## Deliver

- State dimensions and aspect ratio.
- Provide PNG by default and the editable master when useful.
- For multiple destinations, export explicitly named platform files. They may share a composition when dimensions and safe zones are identical, but do not stretch one raster export.
- Use concise Latin filenames such as `logistru-epd-announcement-telegram-1280x1280.png`.
