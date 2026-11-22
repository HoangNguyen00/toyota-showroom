<section class="collection-hero" data-live-catalog aria-label="Khám phá các dòng xe" aria-roledescription="bộ trình chiếu" tabindex="0" data-slider>
    <div class="stage-eyebrow"><h1>KHÁM PHÁ CÁC DÒNG XE</h1><span>TOYOTA / COLLECTION</span></div>
    <div class="car-stage">
        <div class="model-title-window" aria-hidden="true">
            @foreach ($slides as $slide)
                <p class="model-backdrop {{ $loop->first ? 'model-long is-title-active' : '' }}" data-model-title>{{ $slide['headline'] }}</p>
            @endforeach
        </div>
        @foreach ($slides as $slide)
            <div class="car-slide {{ $loop->first ? 'is-active' : '' }}" data-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}" style="--slide-offset: {{ $loop->index * 115 }}%">
                <img src="{{ asset('assets/collection/'.$slide['image']) }}" alt="Toyota {{ $slide['name'] }}, góc ngang" width="1100" height="400" fetchpriority="{{ $loop->first ? 'high' : 'auto' }}" draggable="false">
            </div>
        @endforeach
    </div>
    <button class="button slider-arrow slider-prev" type="button" aria-label="Xe trước" data-prev disabled>←</button>
    <button class="button slider-arrow slider-next" type="button" aria-label="Xe tiếp theo" data-next>→</button>
    <div class="model-caption" aria-live="polite" aria-atomic="true">
        <h2 data-model-name>Toyota Corolla Cross</h2><p data-model-tagline>Dành cho những hành trình mới.</p>
    </div>
    <div class="hero-actions"><a class="button button-dark" data-hero-book href="{{ $slides[0]['booking_url'] }}">Đặt lịch xem xe</a><a class="button" data-hero-detail href="{{ $slides[0]['detail_url'] }}">Khám phá dòng xe</a></div>
    <div class="slide-pagination"><span data-slide-count>01 / 03</span><span>COROLLA CROSS <i>/</i> VIOS <i>/</i> INNOVA</span></div>
    <script type="application/json" data-slide-data>@json($slides)</script>
</section>
