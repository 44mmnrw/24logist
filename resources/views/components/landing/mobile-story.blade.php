@php
    $mobileSection = $landing->section('mobile');
    $driverSection = $landing->section('driver_cabinet');
@endphp

@if ($mobileSection || $driverSection)
<section class="mobile-story" data-mobile-story data-scroll-story aria-label="Мобильная версия и личный кабинет водителя">
    <div class="mobile-story__sticky" data-mobile-story-sticky data-scroll-sticky>
        <div class="mobile-story__stage" data-mobile-story-stage data-scroll-stage>
            @if ($mobileSection)
                <div class="mobile-story__slide" data-mobile-story-slide="mobile" data-scroll-slide>
                    @include('components.landing.mobile')
                </div>
            @endif
            @if ($driverSection)
                <div class="mobile-story__slide" data-mobile-story-slide="driver_cabinet" data-scroll-slide>
                    @include('components.landing.driver-cabinet')
                </div>
            @endif
        </div>
    </div>
</section>
@endif
