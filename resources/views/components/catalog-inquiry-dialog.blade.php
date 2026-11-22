<dialog class="workspace-inquiry-dialog" data-catalog-inquiry aria-labelledby="catalog-inquiry-title">
    <form method="dialog"><button class="workspace-dialog-close" aria-label="Đóng yêu cầu tư vấn">Đóng ×</button></form>
    <p class="customer-eyebrow">TOYOTA / TƯ VẤN</p><h2 id="catalog-inquiry-title">Trao đổi cùng showroom</h2>
    <p data-catalog-car-name>Showroom sẽ liên hệ với bạn để hỗ trợ.</p>
    @if (!auth()->check() || auth()->user()->role === \App\Enums\UserRole::Customer)
    <form data-catalog-inquiry-form method="POST" action="{{ route('inquiries.store') }}" @auth data-chat-url="{{ route('customer.chat.index') }}" @endauth>
        @csrf<input type="hidden" name="car_id" data-catalog-car-id>
        <label>Họ và tên<input name="name" required maxlength="100" autocomplete="name" value="{{ auth()->user()?->name }}"></label>
        <label>Số điện thoại<input name="phone" required maxlength="10" pattern="0[3-9][0-9]{8}" inputmode="tel" autocomplete="tel" value="{{ auth()->user()?->phone }}"></label>
        <label>Email (không bắt buộc)<input name="email" type="email" maxlength="255" autocomplete="email" value="{{ auth()->user()?->email }}"></label>
        <label>Nhu cầu của bạn<textarea name="content" required minlength="10" maxlength="2000" rows="4"></textarea></label>
        <p class="workspace-inquiry-feedback" data-catalog-inquiry-feedback role="status" aria-live="polite" hidden></p>
        <button class="button button-red" type="submit">Gửi yêu cầu tư vấn</button>
    </form>
    @else
        <p>Bạn đang dùng tài khoản nhân viên. Hãy vào khu vực tư vấn để xử lý hồ sơ khách hàng.</p>
        <a class="button button-red" href="{{ route(auth()->user()->role === \App\Enums\UserRole::Admin ? 'admin.inquiries.index' : 'staff.inquiries.index') }}">Quản lý tư vấn</a>
    @endif
</dialog>
