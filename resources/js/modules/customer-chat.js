import { mountInquiryChat } from './chat/inquiry-chat';

const chat = document.querySelector('[data-customer-chat]');
if (chat) {
    const buttons = [...chat.querySelectorAll('[data-conversation]')];
    const threads = [...chat.querySelectorAll('[data-chat-thread]')];
    buttons.forEach((button) => button.addEventListener('click', (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const thread = threads.find((item) => item.dataset.chatThread === button.dataset.conversation);
        if (!thread) return;
        event.preventDefault();
        buttons.forEach((item) => {
            item.classList.toggle('is-selected', item === button);
            item.setAttribute('aria-current', String(item === button));
        });
        threads.forEach((item) => { item.hidden = item !== thread; });
        chat.classList.add('is-thread-open');
        mountInquiryChat(thread);
        history.replaceState(null, '', button.href);
    }));
    chat.querySelectorAll('[data-chat-back]').forEach((button) => button.addEventListener('click', (event) => {
        event.preventDefault();
        chat.classList.remove('is-thread-open');
        history.replaceState(null, '', button.href);
        chat.querySelector('[data-conversation][aria-current="true"]')?.focus();
    }));
    threads.forEach((thread) => {
        const input = thread.querySelector('textarea');
        const counter = thread.querySelector('[data-chat-count]');
        const count = () => { if (counter) counter.textContent = `${input.value.length} / 1000`; };
        input?.addEventListener('input', count);
        // Sent responses can clear the draft without a new input event.
        thread.querySelector('[data-chat-form]')?.addEventListener('submit', () => {
            const watcher = new MutationObserver(() => { count(); watcher.disconnect(); });
            watcher.observe(thread.querySelector('[data-chat-status]'), { childList: true });
        });
    });
    window.addEventListener('beforeunload', (event) => {
        if (threads.some((thread) => thread.querySelector('textarea')?.value.trim())) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}

const inquiryDialog = document.querySelector('[data-inquiry-dialog]');
if (inquiryDialog) {
    document.querySelectorAll('[data-open-inquiry]').forEach((button) => button.addEventListener('click', () => inquiryDialog.showModal()));
    const form = inquiryDialog.querySelector('[data-inquiry-create]');
    const submit = form.querySelector('button[type="submit"]');
    const feedback = form.querySelector('[data-inquiry-feedback]');
    let busy = false;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;
        busy = true; submit.disabled = true; submit.textContent = 'Đang gửi…'; feedback.hidden = true;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
            const body = await response.json();
            if (response.status !== 201 || !Number.isInteger(body.id)) {
                throw new Error(body.errors ? Object.values(body.errors).flat().join(' ') : body.message || 'Chưa gửi được yêu cầu. Hãy thử lại.');
            }
            const url = new URL(form.dataset.chatUrl, location.origin);
            url.searchParams.set('conversation', body.id);
            location.assign(url);
        } catch (error) {
            feedback.textContent = `Chưa gửi được: ${error.message}`; feedback.hidden = false;
        } finally { busy = false; submit.disabled = false; submit.textContent = 'Gửi yêu cầu tư vấn'; }
    });
}
