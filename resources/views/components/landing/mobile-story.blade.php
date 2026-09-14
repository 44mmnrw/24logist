@php
    $mobileSection = $landing->section('mobile');
    $driverSection = $landing->section('driver_cabinet');
@endphp

@if ($mobileSection || $driverSection)
<section class="mobile-story" data-mobile-carousel aria-label="Мобильная версия и личный кабинет водителя">
    <div class="mobile-story__stage" data-mobile-stage>
            @if ($mobileSection)
                <div class="mobile-story__slide" data-mobile-slide="mobile" aria-hidden="false">
                    @include('components.landing.mobile')
                </div>
            @endif
            @if ($driverSection)
                <div class="mobile-story__slide" data-mobile-slide="driver_cabinet" aria-hidden="{{ $mobileSection ? 'true' : 'false' }}" @if ($mobileSection) inert @endif>
                    @include('components.landing.driver-cabinet')
                </div>
            @endif
    </div>
    @if ($mobileSection && $driverSection)
        <div class="mobile-story__dots" role="group" aria-label="Переключить раздел">
            <button class="mobile-story__dot is-active" type="button" data-mobile-select="mobile" aria-label="Показать мобильную версию" aria-pressed="true"></button>
            <button class="mobile-story__dot" type="button" data-mobile-select="driver_cabinet" aria-label="Показать личный кабинет водителя" aria-pressed="false"></button>
        </div>
    @endif
</section>
@endif
