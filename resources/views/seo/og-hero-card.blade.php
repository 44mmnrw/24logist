<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>OG Hero</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            width: 1200px;
            height: 630px;
            overflow: hidden;
            font-family: "Inter", "Segoe UI", system-ui, sans-serif;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        .og-hero {
            width: 1200px;
            height: 630px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            align-items: center;
            gap: 40px;
            padding: 56px 64px;
        }
        .og-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.2;
            max-width: 100%;
        }
        .og-hero__badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #1d4ed8;
            flex-shrink: 0;
        }
        .og-hero h1 {
            margin-top: 20px;
            font-size: 42px;
            line-height: 1.12;
            font-weight: 700;
            color: #0f172b;
            max-width: 620px;
        }
        .og-hero__subtitle {
            margin-top: 12px;
            font-size: 28px;
            line-height: 1.25;
            font-weight: 400;
            color: #0f172b;
            max-width: 620px;
        }
        .og-hero__list {
            margin-top: 28px;
            list-style: none;
            display: grid;
            gap: 10px;
        }
        .og-hero__list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 17px;
            line-height: 1.45;
            color: #314158;
        }
        .og-hero__check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #1d4ed8;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .og-hero__actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .og-hero__btn-primary {
            display: inline-flex;
            align-items: center;
            padding: 12px 22px;
            border-radius: 10px;
            background: #1d4ed8;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
        }
        .og-hero__btn-ghost {
            display: inline-flex;
            align-items: center;
            padding: 12px 22px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            color: #0f172b;
            font-size: 16px;
            font-weight: 600;
            background: #fff;
        }
        .og-hero__hint {
            margin-top: 20px;
            font-size: 15px;
            color: #62748e;
        }
        .og-hero__visual {
            width: 100%;
            height: 100%;
            min-height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .og-hero__card {
            width: 100%;
            max-width: 520px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1), 0 8px 10px rgba(0, 0, 0, 0.08);
            padding: 16px;
        }
        .og-hero__card img {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .og-hero__card-placeholder {
            width: 100%;
            aspect-ratio: 4/3;
            border-radius: 8px;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1d4ed8;
            font-size: 22px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="og-hero">
        <div class="og-hero__content">
            @if (filled($card['badge'] ?? null))
                <div class="og-hero__badge">
                    <span class="og-hero__badge-dot"></span>
                    <span>{{ $card['badge'] }}</span>
                </div>
            @endif

            <h1>{{ $card['title'] }}</h1>

            @if (filled($card['subtitle'] ?? null))
                <p class="og-hero__subtitle">{{ $card['subtitle'] }}</p>
            @endif

            @if (! empty($card['bullets']))
                <ul class="og-hero__list">
                    @foreach ($card['bullets'] as $bullet)
                        <li>
                            <span class="og-hero__check">✓</span>
                            <span>{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (filled($card['primary_button'] ?? null) || filled($card['secondary_button'] ?? null))
                <div class="og-hero__actions">
                    @if (filled($card['primary_button'] ?? null))
                        <span class="og-hero__btn-primary">{{ $card['primary_button'] }}</span>
                    @endif
                    @if (filled($card['secondary_button'] ?? null))
                        <span class="og-hero__btn-ghost">{{ $card['secondary_button'] }}</span>
                    @endif
                </div>
            @endif

            @if (filled($card['hint'] ?? null))
                <p class="og-hero__hint">{{ $card['hint'] }}</p>
            @endif
        </div>

        <div class="og-hero__visual">
            <div class="og-hero__card">
                @if (filled($card['image_url'] ?? null))
                    <img src="{{ $card['image_url'] }}" alt="{{ $card['brand'] }}">
                @else
                    <div class="og-hero__card-placeholder">{{ $card['brand'] }}</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
