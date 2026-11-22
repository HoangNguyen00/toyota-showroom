import { createEcho } from '../../echo';

// Shared transport for staff and customer UI. Mount once, lazily per visible thread.
const mountedThreads = new WeakSet();
let sharedEcho;
export function mountInquiryChat(root) {
    if (mountedThreads.has(root)) return;
    mountedThreads.add(root);
    const id = root.dataset.id;
    const version = Number(root.dataset.version);
    const url = `/yeu-cau/${id}/tin-nhan`;
    const list = root.querySelector('[data-chat-messages]');
    const status = root.querySelector('[data-chat-status]');
    const form = root.querySelector('[data-chat-form]');
    const older = root.querySelector('[data-chat-older]');
    const scroll = root.querySelector('[data-chat-scroll]');
    let loadingOlder = false;
    const messages = new Map();
    let synced = 0;
    let oldestLoaded = null;
    let initialized = false;
    let pending = null;
    let busy = false;
    let draftRevision = 0;
    form?.elements.content.addEventListener('input', () => { draftRevision += 1; });
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const render = (message) => {
        const followLatest = scroll && !loadingOlder && (messages.size === 0 || scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 80);
        messages.set(message.id, message);
        list.replaceChildren(...[...messages.values()].sort((a, b) => a.id - b.id).map((item) => {
            const li = document.createElement('li');
            if (root.dataset.skin === 'customer') {
                li.className = `chat-message chat-message-${Number(item.sender.id) === Number(root.dataset.userId) ? 'customer' : 'staff'}`;
                const bubble = document.createElement('div');
                const text = document.createElement('p');
                text.textContent = item.content;
                bubble.append(text);
                const meta = document.createElement('span');
                meta.textContent = `${item.sender.name} · ${new Date(item.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}`;
                li.append(bubble, meta);
            } else {
                li.className = 'my-2 whitespace-pre-wrap';
                li.textContent = `${item.sender.name}: ${item.content}`;
            }
            return li;
        }));
        if (followLatest) scroll.scrollTop = scroll.scrollHeight;
    };
    const request = async (target, options = {}) => {
        const response = await fetch(target, { ...options, headers: {
            Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...options.headers,
        } });
        const body = await response.json();
        if (!response.ok) throw new Error(body.message || `HTTP ${response.status}`);
        if (body.access_version && body.access_version !== version) {
            throw new Error('Quyền đã thay đổi. Hãy tải lại trang.');
        }
        return body;
    };
    const history = async (query = '') => {
        const body = await request(`${url}${query}`);
        body.data.forEach(render);
        if (!query.includes('before_id')) synced = Math.max(synced, body.next_after_id);
        if (!query.includes('after_id') && body.next_before_id !== null) {
            oldestLoaded = oldestLoaded === null ? body.next_before_id : Math.min(oldestLoaded, body.next_before_id);
        }
        return body;
    };
    const catchUp = async () => {
        try {
            if (!initialized) { await history(); initialized = true; }
            let body;
            do {
                body = await history(`?after_id=${synced}`);
            } while (body.has_more);
            status.textContent = 'Đã kết nối và đồng bộ.';
        } catch (error) { status.textContent = error.message; }
    };
    older.addEventListener('click', async () => {
        const height = scroll?.scrollHeight ?? 0;
        const top = scroll?.scrollTop ?? 0;
        loadingOlder = true;
        try {
            const body = await history(oldestLoaded === null ? '' : `?before_id=${oldestLoaded}`);
            older.disabled = !body.has_more;
            if (scroll) scroll.scrollTop = top + scroll.scrollHeight - height;
        } catch (error) { status.textContent = error.message; }
        finally { loadingOlder = false; }
    });
    // History stays readable even while WebSocket is unavailable.
    if (root.dataset.skin === 'customer') {
        history().then((body) => {
            initialized = true;
            older.disabled = !body.has_more;
        }).catch((error) => { status.textContent = `Không tải được lịch sử: ${error.message}`; });
    }
    let echo;
    try { echo = sharedEcho ??= createEcho(); }
    catch (error) { status.textContent = 'Chưa thể kết nối tin nhắn. Kiểm tra cấu hình hoặc tải lại trang.'; }
    if (echo) {
    echo.private(`inquiry.${id}.v${version}`)
        .listen('.message.created', render)
        .subscribed(catchUp)
        .error(() => { status.textContent = 'Không được vào kênh. Hãy tải lại trang.'; });
    echo.connector.pusher.connection.bind('disconnected', () => {
        status.textContent = 'Mất kết nối. Tin chưa gửi được giữ để thử lại.';
    });
    echo.connector.pusher.connection.bind('unavailable', () => {
        status.textContent = 'Không thể kết nối WebSocket.';
    });
    }
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        const content = form.elements.content.value.trim();
        if (!content) return;
        if (!pending || pending.content !== content) pending = { content, client_message_id: crypto.randomUUID() };
        const submitted = pending;
        const submittedDraft = form.elements.content.value;
        const submittedRevision = draftRevision;
        busy = true;
        form.querySelector('button').disabled = true;
        status.textContent = 'Đang gửi…';
        try {
            render(await request(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(submitted) }));
            if (draftRevision === submittedRevision && form.elements.content.value === submittedDraft) {
                form.elements.content.value = '';
            }
            pending = null;
            status.textContent = 'Đã lưu tin nhắn.';
        } catch (error) { status.textContent = `Gửi thất bại: ${error.message}. Có thể thử lại.`; }
        finally { busy = false; form.querySelector('button').disabled = false; }
    });
    window.addEventListener('pagehide', () => echo?.disconnect());
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) echo?.connect();
    });
}
document.querySelectorAll('[data-inquiry-chat]').forEach((root) => {
    if (!root.hidden) mountInquiryChat(root);
});
