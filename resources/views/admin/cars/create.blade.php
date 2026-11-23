@extends('layouts.staff')
@section('title', 'Thêm xe')
@section('eyebrow', 'Catalog')
@section('lead', 'Mỗi bản ghi là một chiếc xe tại showroom. Có thể chọn ảnh ngay ở phần "Ảnh xe" trong form.')
@section('back')
    <a class="staff-back" href="{{ route('admin.cars.index') }}"><span aria-hidden="true">←</span> Danh sách xe</a>
@endsection
@section('content')
    <div class="staff-card">
        @include('admin.cars._form')
    </div>
@endsection
