const dialog = document.querySelector('.preview-dialog');
document.querySelectorAll('[data-preview]').forEach((button) => {
    button.addEventListener('click', () => {
        dialog.querySelector('#preview-title').textContent = button.dataset.preview;
        dialog.querySelector('#preview-description').textContent = 'Chức năng này sẽ được kết nối ở bước tiếp theo. Hiện bạn đang xem bản mẫu trang Dòng xe; chưa có yêu cầu nào được gửi.';
        dialog.showModal();
    });
});
dialog?.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
const menu = document.querySelector('.menu-toggle');
menu?.addEventListener('click', () => {
    const open = menu.getAttribute('aria-expanded') !== 'true';
    menu.setAttribute('aria-expanded', String(open));
    document.querySelector('.main-nav').classList.toggle('is-open', open);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        menu?.setAttribute('aria-expanded', 'false');
        document.querySelector('.main-nav')?.classList.remove('is-open');
    }
});
const slider = document.querySelector('[data-slider]');
if (slider) {
    const data = JSON.parse(slider.querySelector('[data-slide-data]').textContent);
    const slides = [...slider.querySelectorAll('[data-slide]')];
    const previous = slider.querySelector('[data-prev]');
    const next = slider.querySelector('[data-next]');
    let active = 0;
    function show(index) {
        const oldIndex = active;
        active = Math.min(Math.max(index, 0), slides.length - 1);
        if (oldIndex === active) return;
        slider.querySelectorAll('[data-model-title]').forEach((title, i) => {
            title.classList.toggle('is-title-active', i === active);
            title.classList.toggle('is-title-exit', i === oldIndex);
        });
        slides.forEach((slide, i) => {
            slide.style.setProperty('--slide-offset', `${(i - active) * 115}%`);
            slide.classList.toggle('is-active', i === active);
            slide.setAttribute('aria-hidden', String(i !== active));
        });
        previous.disabled = active === 0;
        next.disabled = active === slides.length - 1;
        slider.querySelector('[data-model-name]').textContent = `Toyota ${data[active].name}`;
        slider.querySelector('[data-model-tagline]').textContent = data[active].tagline;
        for (const text of slider.querySelectorAll('[data-model-name], [data-model-tagline]')) {
            text.getAnimations().forEach((animation) => animation.cancel());
            text.animate([{ transform: 'translateY(18px)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }], { duration: 700, easing: 'cubic-bezier(.22,1,.36,1)' });
        }
        slider.querySelector('[data-slide-count]').textContent = `${String(active + 1).padStart(2, '0')} / ${String(data.length).padStart(2, '0')}`;
        slider.querySelector('[data-hero-detail]').href = data[active].detail_url || `/xe-preview/${active + 3}`;
        const booking = slider.querySelector('[data-hero-book]');
        if (slider.hasAttribute('data-live-catalog')) {
            booking.href = data[active].booking_url;
        } else {
            booking.dataset.preview = `Đặt lịch xem Toyota ${data[active].name}`;
            booking.disabled = active === 2;
            booking.textContent = active === 2 ? 'Xe minh họa đã bán' : 'Đặt lịch xem xe';
        }
    }
    previous.addEventListener('click', () => show(active - 1));
    next.addEventListener('click', () => show(active + 1));
    slider.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            show(active + (event.key === 'ArrowRight' ? 1 : -1));
        }
    });
    let touchStart;
    slider.addEventListener('touchstart', (event) => { touchStart = { x: event.touches[0].clientX, y: event.touches[0].clientY }; }, { passive: true });
    slider.addEventListener('touchend', (event) => {
        if (!touchStart) return;
        const dx = event.changedTouches[0].clientX - touchStart.x;
        const dy = event.changedTouches[0].clientY - touchStart.y;
        if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) show(active + (dx < 0 ? 1 : -1));
        touchStart = null;
    }, { passive: true });
}
const form = document.querySelector('[data-filters]');
if (form) {
    const grid = document.querySelector('[data-car-grid]');
    const cards = [...grid.querySelectorAll('[data-car]')];
    const keys = ['q', 'category', 'price', 'status', 'sort'];
    const normalize = (value) => value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
    function update(writeUrl = true) {
        const values = Object.fromEntries(new FormData(form));
        const query = normalize(values.q.trim());
        let count = 0;
        const sorted = [...cards].sort((a, b) => values.sort === 'price_asc' ? Number(a.dataset.price) - Number(b.dataset.price) : values.sort === 'price_desc' ? Number(b.dataset.price) - Number(a.dataset.price) : cards.indexOf(a) - cards.indexOf(b));
        sorted.forEach((card) => {
            const price = Number(card.dataset.price);
            const priceMatch = !values.price || (values.price === 'under-2' && price < 2e9) || (values.price === '2-to-3' && price >= 2e9 && price <= 3e9) || (values.price === 'over-3' && price > 3e9);
            const visible = normalize(`${card.dataset.name} ${card.dataset.code}`).includes(query) && (!values.category || card.dataset.category === values.category) && (!values.status || card.dataset.status === values.status) && priceMatch;
            card.hidden = !visible;
            if (visible) count++;
            grid.append(card);
        });
        const filtered = keys.some((key) => key !== 'sort' && values[key]);
        document.querySelector('[data-empty]').hidden = count !== 0;
        document.querySelector('[data-results]').textContent = `${String(count).padStart(2, '0')} xe trong bản mẫu`;
        document.querySelector('[data-filter-summary]').hidden = !filtered;
        document.querySelector('[data-filter-label]').textContent = `${count} kết quả phù hợp`;
        if (writeUrl) {
            const url = new URL(window.location.href);
            keys.forEach((key) => { if (values[key] && !(key === 'sort' && values[key] === 'newest')) url.searchParams.set(key, values[key]); else url.searchParams.delete(key); });
            history.replaceState(null, '', url);
        }
    }
    function restore() {
        const query = new URLSearchParams(location.search);
        keys.forEach((key) => { form.elements[key].value = query.get(key) ?? (key === 'sort' ? 'newest' : ''); });
        if (!form.elements.sort.value) form.elements.sort.value = 'newest';
        update(false);
    }
    form.addEventListener('submit', (event) => { event.preventDefault(); update(); });
    form.addEventListener('input', () => update());
    form.addEventListener('change', () => update());
    document.querySelectorAll('[data-reset]').forEach((button) => button.addEventListener('click', () => { form.reset(); update(); form.elements.q.focus(); }));
    window.addEventListener('popstate', restore);
    restore();
}
