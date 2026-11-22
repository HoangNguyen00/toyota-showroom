@foreach ($cars as $car)
    @php($presented = \App\Support\Catalog\CarPresenter::fromCar($car))
    <div class="saved-item" data-saved-item data-car-id="{{ $car->id }}">
        <x-car-card :car="$presented" />
        @if ($compareUrl)
            <label class="saved-pick"><input type="checkbox" value="{{ $car->id }}" data-saved-pick> Chọn để so sánh<span class="sr-only"> {{ $car->name }} {{ $car->code }}</span></label>
        @endif
    </div>
@endforeach
