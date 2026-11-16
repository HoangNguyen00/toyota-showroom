@extends('layouts.app')

@section('title', 'Dịch vụ — Toyota Showroom')

@section('content')
<div class="appointment-page">
    <div class="appointment-shell">
        <p class="appointment-kicker">TOYOTA SHOWROOM</p>
        <h1>Dịch vụ &amp; bảo dưỡng</h1>
        <p class="appointment-lead">Tổng quan các dịch vụ bảo hành và bảo dưỡng chính hãng Toyota của showroom.</p>

        <div class="service-list">
            @forelse($services as $service)
                <article class="service-item">
                    <div>
                        <h2>{{ $service->name }}</h2>
                        <span class="service-type">{{ $service->type->value === 'warranty' ? 'Bảo hành' : 'Bảo dưỡng' }}</span>
                        <p>{{ $service->description }}</p>
                    </div>
                    @if ($service->allow_booking)
                        <a class="button" href="{{ route('appointments.create', ['type' => 'maintenance']) }}">Đặt lịch</a>
                    @else
                        <a class="button button--muted" href="{{ url('/').'#lien-he' }}">Liên hệ showroom</a>
                    @endif
                </article>
            @empty
                <div class="appointment-empty">Chưa có dịch vụ nào được công bố. Vui lòng liên hệ showroom.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
