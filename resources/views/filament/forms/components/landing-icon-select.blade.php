@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $modalId = $getId().'-catalog';
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
>
    <div
        class="landing-icon-picker"
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            catalog: {},
            results: [],
            query: '',
            variant: 'all',
            category: 'all',
            limit: 120,
            loading: true,
            error: false,
            catalogUrl: @js($field->getCatalogUrl()),
            outlineSprite: @js($field->getOutlineSpriteUrl()),
            filledSprite: @js($field->getFilledSpriteUrl()),
            async init() {
                this.$watch('query', () => this.refresh())
                this.$watch('variant', () => this.refresh())
                this.$watch('category', () => this.refresh())

                try {
                    window.__landingTablerIconCatalogPromise ??= fetch(this.catalogUrl, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    }).then((response) => {
                        if (! response.ok) throw new Error('Icon catalog is unavailable')

                        return response.json()
                    })

                    this.catalog = await window.__landingTablerIconCatalogPromise
                    this.refresh()
                } catch (error) {
                    this.error = true
                } finally {
                    this.loading = false
                }
            },
            normalize(value) {
                return String(value ?? '').toLocaleLowerCase('ru-RU').replaceAll('ё', 'е').trim()
            },
            refresh() {
                const query = this.normalize(this.query)
                const results = []

                for (const [slug, meta] of Object.entries(this.catalog)) {
                    if (query && ! this.normalize(`${slug} ${meta.title} ${meta.search ?? ''}`).includes(query)) continue
                    if (this.category !== 'all' && ! (meta.categories ?? []).includes(this.category)) continue

                    if (this.variant !== 'filled') {
                        results.push({
                            value: `tabler:${slug}`,
                            slug,
                            title: meta.title,
                            variant: 'outline',
                        })
                    }

                    if ((this.variant !== 'outline') && meta.filled) {
                        results.push({
                            value: `tabler-filled:${slug}`,
                            slug,
                            title: meta.title,
                            variant: 'filled',
                        })
                    }
                }

                this.results = results
                this.limit = 120
            },
            href(icon) {
                return icon.variant === 'filled'
                    ? `${this.filledSprite}#tabler-filled-${icon.slug}`
                    : `${this.outlineSprite}#tabler-${icon.slug}`
            },
            hrefForValue(value) {
                if (! value) return ''

                if (value.startsWith('tabler-filled:')) {
                    const slug = value.substring('tabler-filled:'.length)
                    return `${this.filledSprite}#tabler-filled-${slug}`
                }

                const slug = value.replace(/^tabler:/, '')
                return `${this.outlineSprite}#tabler-${slug}`
            },
            selectedSlug() {
                return String(this.state ?? '').replace(/^tabler-filled:/, '').replace(/^tabler:/, '')
            },
            selectedTitle() {
                const slug = this.selectedSlug()
                return this.catalog[slug]?.title ?? slug ?? ''
            },
            selectedVariant() {
                return String(this.state ?? '').startsWith('tabler-filled:') ? 'Заливка' : 'Контур'
            },
            choose(icon) {
                this.state = icon.value
                this.$dispatch('close-modal', { id: @js($modalId) })
            },
        }"
    >
        <div class="landing-icon-picker__value">
            <div class="landing-icon-picker__selection" x-show="state" x-cloak>
                <span class="landing-icon-picker__preview">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <use x-bind:href="hrefForValue(state)"></use>
                    </svg>
                </span>
                <span class="landing-icon-picker__selection-text">
                    <strong x-text="selectedTitle()"></strong>
                    <small><span x-text="selectedSlug()"></span> · <span x-text="selectedVariant()"></span></small>
                </span>
            </div>

            <span class="landing-icon-picker__empty" x-show="! state">Иконка не выбрана</span>

            <div class="landing-icon-picker__actions">
                @if (! $isRequired())
                    <button
                        type="button"
                        class="landing-icon-picker__clear"
                        x-show="state"
                        x-on:click="state = null"
                    >
                        Очистить
                    </button>
                @endif

                <x-filament::modal
                    :id="$modalId"
                    heading="Выбор иконки Tabler"
                    description="Все контурные и залитые иконки. Поиск работает на русском и английском."
                    width="6xl"
                    sticky-header
                >
                    <x-slot name="trigger">
                        <button
                            type="button"
                            class="landing-icon-picker__open"
                            @disabled($isDisabled())
                        >
                            Открыть каталог
                        </button>
                    </x-slot>

                    <div class="landing-icon-catalog">
                        <div class="landing-icon-catalog__toolbar">
                            <input
                                type="search"
                                class="landing-icon-catalog__search"
                                placeholder="Например: грузовик, документ, arrow…"
                                x-model.debounce.150ms="query"
                            />

                            <select
                                class="landing-icon-catalog__category"
                                x-model="category"
                                aria-label="Категория иконок"
                            >
                                <option value="all">Все категории</option>
                                @foreach ($field->getCategories() as $category => $label)
                                    <option value="{{ $category }}">{{ $label }}</option>
                                @endforeach
                            </select>

                            <div class="landing-icon-catalog__variants">
                                <button type="button" x-bind:class="{ 'is-active': variant === 'all' }" x-on:click="variant = 'all'">Все</button>
                                <button type="button" x-bind:class="{ 'is-active': variant === 'outline' }" x-on:click="variant = 'outline'">Контур</button>
                                <button type="button" x-bind:class="{ 'is-active': variant === 'filled' }" x-on:click="variant = 'filled'">Заливка</button>
                            </div>
                        </div>

                        <div class="landing-icon-catalog__status" x-show="loading">Загружаем каталог…</div>
                        <div class="landing-icon-catalog__status landing-icon-catalog__status--error" x-show="error">Не удалось загрузить каталог иконок.</div>
                        <div class="landing-icon-catalog__status" x-show="! loading && ! error && ! results.length">Иконки не найдены.</div>

                        <div class="landing-icon-catalog__summary" x-show="! loading && results.length">
                            Найдено: <strong x-text="results.length"></strong>
                        </div>

                        <div class="landing-icon-catalog__grid" x-show="! loading && results.length">
                            <template x-for="icon in results.slice(0, limit)" x-bind:key="icon.value">
                                <button
                                    type="button"
                                    class="landing-icon-catalog__item"
                                    x-bind:class="{ 'is-selected': state === icon.value }"
                                    x-on:click="choose(icon)"
                                    x-bind:title="`${icon.title} (${icon.slug})`"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <use x-bind:href="href(icon)"></use>
                                    </svg>
                                    <span x-text="icon.title"></span>
                                    <small x-text="icon.slug"></small>
                                    <em x-text="icon.variant === 'filled' ? 'Заливка' : 'Контур'"></em>
                                </button>
                            </template>
                        </div>

                        <button
                            type="button"
                            class="landing-icon-catalog__more"
                            x-show="limit < results.length"
                            x-on:click="limit += 120"
                        >
                            Показать ещё
                        </button>
                    </div>
                </x-filament::modal>
            </div>
        </div>
    </div>
</x-dynamic-component>

@once
    <style>
        .landing-icon-picker__value { display:flex; align-items:center; gap:.75rem; min-height:2.625rem; padding:.45rem .55rem .45rem .7rem; border:1px solid #d1d5db; border-radius:.5rem; background:#fff; }
        .dark .landing-icon-picker__value { border-color:#4b5563; background:#111827; }
        .landing-icon-picker__selection { display:flex; align-items:center; gap:.65rem; min-width:0; flex:1; }
        .landing-icon-picker__preview { display:grid; place-items:center; width:2rem; height:2rem; flex:none; border-radius:.4rem; background:#eef2ff; color:#1d4ed8; }
        .dark .landing-icon-picker__preview { background:#1e3a8a; color:#bfdbfe; }
        .landing-icon-picker__preview svg { width:1.25rem; height:1.25rem; }
        .landing-icon-picker__selection-text { display:flex; min-width:0; flex-direction:column; line-height:1.2; }
        .landing-icon-picker__selection-text strong, .landing-icon-picker__selection-text small { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .landing-icon-picker__selection-text strong { font-size:.875rem; }
        .landing-icon-picker__selection-text small { margin-top:.15rem; color:#6b7280; font-size:.72rem; }
        .landing-icon-picker__empty { flex:1; color:#9ca3af; font-size:.875rem; }
        .landing-icon-picker__actions { display:flex; align-items:center; gap:.45rem; margin-left:auto; }
        .landing-icon-picker__open, .landing-icon-catalog__more { border:0; border-radius:.45rem; background:#2563eb; color:#fff; cursor:pointer; font-size:.8rem; font-weight:600; padding:.55rem .75rem; }
        .landing-icon-picker__open:hover, .landing-icon-catalog__more:hover { background:#1d4ed8; }
        .landing-icon-picker__open:disabled { cursor:not-allowed; opacity:.5; }
        .landing-icon-picker__clear { border:0; background:transparent; color:#64748b; cursor:pointer; font-size:.76rem; padding:.35rem; }
        .landing-icon-picker__clear:hover { color:#dc2626; }
        .landing-icon-catalog { min-height:24rem; }
        .landing-icon-catalog__toolbar { position:sticky; top:0; z-index:2; display:flex; align-items:center; gap:.75rem; padding-bottom:1rem; background:var(--gray-50, #fff); }
        .dark .landing-icon-catalog__toolbar { background:#111827; }
        .landing-icon-catalog__search { min-width:0; flex:1; }
        .landing-icon-catalog__search, .landing-icon-catalog__category { height:2.65rem; border:1px solid #cbd5e1; border-radius:.5rem; background:#fff; padding:0 .8rem; color:#0f172a; outline:none; }
        .landing-icon-catalog__category { width:12rem; cursor:pointer; }
        .landing-icon-catalog__search:focus, .landing-icon-catalog__category:focus { border-color:#2563eb; box-shadow:0 0 0 1px #2563eb; }
        .dark .landing-icon-catalog__search, .dark .landing-icon-catalog__category { border-color:#4b5563; background:#1f2937; color:#f8fafc; }
        .landing-icon-catalog__variants { display:flex; gap:.25rem; padding:.2rem; border-radius:.55rem; background:#f1f5f9; }
        .dark .landing-icon-catalog__variants { background:#1f2937; }
        .landing-icon-catalog__variants button { border:0; border-radius:.4rem; background:transparent; color:#64748b; cursor:pointer; font-size:.78rem; font-weight:600; padding:.48rem .65rem; }
        .landing-icon-catalog__variants button.is-active { background:#fff; color:#1d4ed8; box-shadow:0 1px 3px rgba(15,23,42,.12); }
        .dark .landing-icon-catalog__variants button.is-active { background:#374151; color:#bfdbfe; }
        .landing-icon-catalog__summary { margin-bottom:.75rem; color:#64748b; font-size:.78rem; }
        .landing-icon-catalog__status { display:grid; min-height:18rem; place-items:center; color:#64748b; }
        .landing-icon-catalog__status--error { color:#dc2626; }
        .landing-icon-catalog__grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(9.5rem, 1fr)); gap:.6rem; }
        .landing-icon-catalog__item { position:relative; display:flex; min-width:0; min-height:8.25rem; flex-direction:column; align-items:center; justify-content:center; border:1px solid #e2e8f0; border-radius:.65rem; background:#fff; color:#0f172a; cursor:pointer; padding:.75rem .5rem; text-align:center; transition:border-color .15s, box-shadow .15s, transform .15s; }
        .landing-icon-catalog__item:hover { border-color:#60a5fa; box-shadow:0 4px 14px rgba(37,99,235,.12); transform:translateY(-1px); }
        .landing-icon-catalog__item.is-selected { border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.2); }
        .dark .landing-icon-catalog__item { border-color:#374151; background:#1f2937; color:#f8fafc; }
        .landing-icon-catalog__item svg { width:2rem; height:2rem; margin-bottom:.55rem; color:#1d4ed8; }
        .dark .landing-icon-catalog__item svg { color:#60a5fa; }
        .landing-icon-catalog__item span, .landing-icon-catalog__item small { max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .landing-icon-catalog__item span { font-size:.78rem; font-weight:600; }
        .landing-icon-catalog__item small { margin-top:.2rem; color:#94a3b8; font-size:.66rem; }
        .landing-icon-catalog__item em { position:absolute; top:.4rem; right:.4rem; color:#94a3b8; font-size:.58rem; font-style:normal; }
        .landing-icon-catalog__more { display:block; margin:1rem auto 0; min-width:10rem; }
        @media (max-width: 640px) {
            .landing-icon-catalog__toolbar { align-items:stretch; flex-direction:column; }
            .landing-icon-catalog__category { width:100%; }
            .landing-icon-catalog__variants { justify-content:stretch; }
            .landing-icon-catalog__variants button { flex:1; }
            .landing-icon-catalog__grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .landing-icon-picker__value { align-items:stretch; flex-direction:column; }
            .landing-icon-picker__actions { width:100%; margin-left:0; }
            .landing-icon-picker__open { flex:1; }
        }
    </style>
@endonce
