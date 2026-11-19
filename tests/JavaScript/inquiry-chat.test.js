import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

// Execute the actual module with a minimal DOM/transport boundary, not a replacement composer.
const source = (await readFile(new URL('../../resources/js/modules/chat/inquiry-chat.js', import.meta.url), 'utf8'))
    .replace(/^import .*;\r?\n/, '').replace(/^export /gm, '');

function mount({ echoFails = false } = {}) {
    let submit; let input; let sequence = 0;
    const requests = []; const rows = [];
    const draft = { value: '', addEventListener: (name, handler) => { if (name === 'input') input = handler; } };
    const button = { disabled: false };
    const form = { elements: { content: draft }, querySelector: () => button,
        addEventListener: (_, handler) => { submit = handler; } };
    const status = { textContent: '' };
    const root = { dataset: { id: '1', version: '1' }, querySelector: (selector) => ({
        '[data-chat-form]': form, '[data-chat-status]': status,
        '[data-chat-messages]': { replaceChildren: (...children) => { rows.splice(0, rows.length, ...children); } },
        '[data-chat-older]': { addEventListener() {} },
    }[selector]) };
    const channel = { listen() { return this; }, subscribed() { return this; }, error() { return this; } };
    vm.runInNewContext(source, {
        document: { querySelectorAll: () => [root], querySelector: () => ({ content: 'test-only-csrf' }), createElement: () => ({}) },
        window: { addEventListener() {} }, crypto: { randomUUID: () => `test-uuid-${++sequence}` },
        createEcho: () => {
            if (echoFails) throw new Error('Missing WebSocket config');
            return { private: () => channel, connector: { pusher: { connection: { bind() {} } } } };
        },
        fetch: (_, options) => new Promise((resolve, reject) => requests.push({
            payload: JSON.parse(options.body), reject,
            respond: (statusCode = 201) => resolve({ ok: statusCode < 400, status: statusCode,
                json: async () => statusCode < 400 ? { id: 1, sender: { name: 'Test' }, content: 'A' } : { message: 'Rejected' } }),
        })),
    });
    return { draft, button, status, requests, rows, type(value) { draft.value = value; input(); },
        submit: () => submit({ preventDefault() {} }) };
}

test('late success preserves a newer draft and the next message gets a new UUID', async () => {
    const ui = mount(); ui.type('A');
    const first = ui.submit(); assert.equal(ui.button.disabled, true);
    ui.type('B'); ui.requests[0].respond(); await first;
    assert.equal(ui.draft.value, 'B'); assert.equal(ui.button.disabled, false);
    const next = ui.submit();
    assert.notEqual(ui.requests[1].payload.client_message_id, ui.requests[0].payload.client_message_id);
    assert.equal(ui.requests[1].payload.content, 'B');
    ui.requests[1].respond(); await next; assert.equal(ui.draft.value, '');
});

test('network failure keeps the draft and an unchanged retry uses the same UUID', async () => {
    const ui = mount(); ui.type('A');
    const first = ui.submit(); ui.requests[0].reject(new Error('Network unavailable')); await first;
    assert.equal(ui.draft.value, 'A'); assert.equal(ui.rows.length, 0);
    const retry = ui.submit();
    assert.deepEqual(ui.requests[1].payload, ui.requests[0].payload);
    ui.requests[1].respond(200); await retry;
    assert.equal(ui.draft.value, ''); assert.equal(ui.rows.length, 1);
});

test('editing while a failed request is pending preserves the new draft', async () => {
    const ui = mount(); ui.type('A');
    const first = ui.submit(); ui.type('B'); ui.requests[0].respond(422); await first;
    assert.equal(ui.draft.value, 'B'); assert.equal(ui.rows.length, 0);
    assert.match(ui.status.textContent, /Gửi thất bại/);
});

test('editing away and back is still a changed composer; busy submit does not duplicate HTTP', async () => {
    const ui = mount(); ui.type('A');
    const first = ui.submit(); await ui.submit(); assert.equal(ui.requests.length, 1);
    ui.type('B'); ui.type('A'); ui.requests[0].respond(); await first;
    assert.equal(ui.draft.value, 'A');
});

test('missing WebSocket config does not turn a submit into native navigation', async () => {
    const ui = mount({ echoFails: true });
    assert.match(ui.status.textContent, /Chưa thể kết nối/);
    ui.type('A'); const sending = ui.submit();
    assert.equal(ui.requests.length, 1);
    ui.requests[0].respond(); await sending;
    assert.equal(ui.rows.length, 1); assert.equal(ui.draft.value, '');
});
