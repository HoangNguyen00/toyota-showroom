// TV5: đặt lịch — hiện đúng phần xe (viewing) hoặc dịch vụ (maintenance) theo radio,
// nhấn submit đang tải, và giữ nền tối đồng bộ cho header/footer.
const form = document.querySelector('form.appointment-card');
if (form) {
    const targets = {
        viewing: form.querySelector('[data-appointment-target="viewing"]'),
        maintenance: form.querySelector('[data-appointment-target="maintenance"]'),
    };
    function syncTargets() {
        const type = form.querySelector('input[name="type"]:checked')?.value;
        for (const [key, node] of Object.entries(targets)) {
            if (node) node.hidden = key !== type;
        }
    }
    form.querySelectorAll('input[name="type"]').forEach((radio) => radio.addEventListener('change', syncTargets));
    syncTargets();

    const submit = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', () => {
        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Đang gửi yêu cầu…';
        }
    });
}

// Đánh dấu body các trang appointment để header/footer dùng nền tối đồng bộ.
const page = document.querySelector('.appointment-page');
if (page) document.body.classList.add('dark-page');
