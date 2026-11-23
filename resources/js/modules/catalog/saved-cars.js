import { createSavedCarsStore, DEFAULT_LIMIT, STORAGE_KEY } from './saved-cars-store';

function browserStorage() {
    try { return window.localStorage; } catch { return null; }
}

const page = document.querySelector('[data-saved-cars-page]');
const limit = Number(page?.dataset.savedLimit) || DEFAULT_LIMIT;
const store = createSavedCarsStore(browserStorage(), limit);
const liveRegion = (() => {
    const existing = document.querySelector('[data-saved-status]');
    if (existing) return existing;
    // Ngoài trang Xe quan tâm: toast nhìn thấy được, đồng thời là vùng đọc màn hình.
    const region = document.createElement('p');
    region.className = 'saved-toast';
    region.setAttribute('role', 'status');
    region.setAttribute('aria-live', 'polite');
    document.body.append(region);
    return region;
})();
let toastTimer;

function announce(message) {
    liveRegion.textContent = '';
    window.clearTimeout(toastTimer);
    window.setTimeout(() => { liveRegion.textContent = message; }, 30);
    if (!page) toastTimer = window.setTimeout(() => { liveRegion.textContent = ''; }, 5000);
}

function paintButton(button) {
    const saved = store.has(button.dataset.saveCarId);
    button.hidden = false;
    button.setAttribute('aria-pressed', String(saved));
    button.classList.toggle('is-saved', saved);
    button.querySelector('[data-save-icon]').textContent = saved ? '♥' : '♡';
    button.querySelector('[data-save-label]').textContent = saved ? 'Đã lưu' : 'Lưu xe';
    if (!store.available) {
        // Vẫn focus được bằng bàn phím; bấm sẽ hiện thông báo lý do thay vì im lặng.
        button.setAttribute('aria-disabled', 'true');
        button.title = 'Trình duyệt đang chặn bộ nhớ cục bộ nên chưa lưu được xe.';
    }
}

function paintCounts() {
    const count = store.ids().length;
    document.querySelectorAll('[data-saved-count]').forEach((badge) => {
        badge.hidden = !store.available || count === 0;
        badge.textContent = `(${count})`;
    });
}

function paintAll(root = document) {
    root.querySelectorAll('[data-save-car]').forEach(paintButton);
    paintCounts();
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-save-car]');
    if (!button) return;
    const result = store.toggle(button.dataset.saveCarId);
    const messages = {
        added: 'Đã lưu xe vào danh sách quan tâm.',
        removed: 'Đã bỏ lưu xe.',
        full: `Danh sách đã đủ ${store.limit} xe. Bỏ lưu bớt một chiếc trước khi lưu thêm.`,
        unavailable: 'Trình duyệt đang chặn bộ nhớ cục bộ nên chưa lưu được xe.',
    };
    announce(messages[result]);
    document.querySelectorAll(`[data-save-car][data-save-car-id="${CSS.escape(button.dataset.saveCarId)}"]`).forEach(paintButton);
    paintCounts();
    if (result === 'removed' && page) removeSavedItem(button.dataset.saveCarId);
});

window.addEventListener('storage', (event) => {
    if (event.key !== STORAGE_KEY) return;
    store.reload();
    paintAll();
});

paintAll();

// ----- Trang "Xe quan tâm" -----
const grid = page?.querySelector('[data-saved-grid]');
const empty = page?.querySelector('[data-saved-empty]');
const compare = page?.querySelector('[data-saved-compare]');

function syncEmpty() {
    const count = grid.querySelectorAll('[data-saved-item]').length;
    empty.hidden = count > 0;
    if (compare) compare.hidden = count < 2;
    syncCompare();
}

function syncCompare() {
    if (!compare) return;
    const picks = [...grid.querySelectorAll('[data-saved-pick]')];
    const checked = picks.filter((pick) => pick.checked);
    picks.forEach((pick) => { pick.disabled = !pick.checked && checked.length >= 2; });
    compare.querySelector('[data-saved-compare-submit]').disabled = checked.length !== 2;
    compare.querySelector('[data-saved-compare-hint]').textContent = checked.length === 2
        ? 'Đã chọn 2 xe.' : `Đánh dấu đúng hai chiếc bên dưới (đã chọn ${checked.length}/2).`;
}

function removeSavedItem(id) {
    const item = grid.querySelector(`[data-saved-item][data-car-id="${CSS.escape(String(id))}"]`);
    if (!item) return;
    // Giữ focus cho bàn phím: chuyển sang nút lưu của thẻ kế tiếp/trước, hoặc link trạng thái rỗng.
    const hadFocus = item.contains(document.activeElement);
    const items = [...grid.querySelectorAll('[data-saved-item]')];
    const next = items[items.indexOf(item) + 1] ?? items[items.indexOf(item) - 1];
    item.remove();
    syncEmpty();
    if (hadFocus) (next?.querySelector('[data-save-car]') ?? empty.querySelector('a')).focus();
}

async function loadSavedPage() {
    if (!store.available) {
        page.querySelector('[data-saved-unavailable]').hidden = false;
        grid.setAttribute('aria-busy', 'false');
        return;
    }
    const ids = store.ids();
    if (ids.length === 0) {
        grid.setAttribute('aria-busy', 'false');
        syncEmpty();
        return;
    }
    const status = page.querySelector('[data-saved-status]');
    status.textContent = 'Đang tải xe đã lưu…';
    try {
        const url = new URL(page.dataset.cardsUrl, location.origin);
        url.searchParams.set('ids', ids.join(','));
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(String(response.status));
        const body = await response.json();
        grid.innerHTML = body.html;
        const before = ids.length;
        const after = store.retain(body.ids);
        status.textContent = before > after
            ? `${after} xe đang hiển thị. ${before - after} xe không còn được showroom hiển thị đã được bỏ khỏi danh sách.`
            : `${after} xe đang hiển thị.`;
        paintAll(grid);
    } catch {
        status.textContent = 'Chưa tải được danh sách xe. Vui lòng tải lại trang.';
    } finally {
        grid.setAttribute('aria-busy', 'false');
        syncEmpty();
    }
}

if (page) {
    grid.addEventListener('change', (event) => { if (event.target.matches('[data-saved-pick]')) syncCompare(); });
    compare?.addEventListener('submit', (event) => {
        event.preventDefault();
        const [first, second] = [...grid.querySelectorAll('[data-saved-pick]:checked')].map((pick) => pick.value);
        if (!first || !second) return;
        const url = new URL(page.dataset.compareUrl, location.origin);
        url.searchParams.set('xe1', first);
        url.searchParams.set('xe2', second);
        location.assign(url);
    });
    loadSavedPage();
}
