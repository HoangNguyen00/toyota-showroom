// P2 đề xuất — Xe quan tâm: chỉ lưu ID xe (số nguyên dương) trong localStorage của trình duyệt.
export const STORAGE_KEY = 'toyota-showroom:saved-cars:v1';
export const DEFAULT_LIMIT = 12;

function sanitize(ids, limit) {
    const seen = new Set();
    for (const value of Array.isArray(ids) ? ids : []) {
        const id = Number(value);
        if (Number.isInteger(id) && id > 0 && id < 1e10 && !seen.has(id)) seen.add(id);
        if (seen.size >= limit) break;
    }
    return [...seen];
}

export function createSavedCarsStore(storage, limit = DEFAULT_LIMIT) {
    let available = false;
    let cache = [];
    try {
        // Chế độ riêng tư/chặn bộ nhớ có thể ném lỗi khi ghi: coi như không dùng được.
        const probe = `${STORAGE_KEY}:probe`;
        storage.setItem(probe, '1');
        storage.removeItem(probe);
        available = true;
    } catch {
        available = false;
    }
    if (available) {
        // JSON hỏng không chặn tính năng: bắt đầu lại từ danh sách rỗng.
        try { cache = sanitize(JSON.parse(storage.getItem(STORAGE_KEY) || '[]'), limit); } catch { cache = []; }
    }

    function write(ids) {
        if (!available) return false;
        try {
            storage.setItem(STORAGE_KEY, JSON.stringify(ids));
            cache = ids;
            return true;
        } catch {
            return false;
        }
    }

    return {
        get available() { return available; },
        limit,
        ids: () => [...cache],
        has: (id) => cache.includes(Number(id)),
        /** @returns {'added'|'removed'|'full'|'unavailable'} */
        toggle(id) {
            const value = Number(id);
            if (!available) return 'unavailable';
            if (cache.includes(value)) return write(cache.filter((saved) => saved !== value)) ? 'removed' : 'unavailable';
            if (cache.length >= limit) return 'full';
            return write(sanitize([...cache, value], limit)) ? 'added' : 'unavailable';
        },
        /** Giữ lại đúng các ID server còn trả về (xe ẩn/dòng ẩn/đã xóa bị loại). */
        retain(visibleIds) {
            const keep = new Set(visibleIds.map(Number));
            const next = cache.filter((id) => keep.has(id));
            if (next.length !== cache.length) write(next);
            return cache.length;
        },
        /** Đồng bộ khi tab khác thay đổi danh sách. */
        reload() {
            try { cache = sanitize(JSON.parse(storage.getItem(STORAGE_KEY) || '[]'), limit); } catch { cache = []; }
        },
    };
}
