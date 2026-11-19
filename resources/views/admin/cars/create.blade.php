@extends('layouts.account')
@section('title', 'Thêm xe')
@section('content')
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">Thêm xe</h1>
        <p class="text-sm text-zinc-600">Mỗi bản ghi là một chiếc xe tại showroom. Có thể chọn ảnh ngay ở phần "Ảnh xe" trong form.</p>
        @include('admin.cars._form')
    </div>
@endsection
