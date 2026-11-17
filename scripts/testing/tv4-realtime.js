// Real HTTP/session/CSRF + two independent Reverb WebSocket clients. No demo DB mutations.
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import { randomBytes } from 'node:crypto';
import { connect } from 'node:net';
import { spawn } from 'node:child_process';

// Small RFC6455 test client with explicit local Origin; no extra dependencies.
class LocalWebSocket extends EventTarget {
    buffer = Buffer.alloc(0);
    upgraded = false;
    constructor(path) {
        super();
        this.socket = connect(8081, '127.0.0.1', () => {
            this.socket.write(`GET ${path} HTTP/1.1\r\nHost: 127.0.0.1:8081\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: ${randomBytes(16).toString('base64')}\r\nSec-WebSocket-Version: 13\r\nOrigin: http://127.0.0.1:8001\r\n\r\n`);
        });
        this.socket.on('error', () => this.dispatchEvent(new Event('error')));
        this.socket.on('data', (chunk) => {
            this.buffer = Buffer.concat([this.buffer, chunk]);
            if (!this.upgraded) {
                const end = this.buffer.indexOf('\r\n\r\n');
                if (end < 0) return;
                assert.match(this.buffer.subarray(0, end).toString(), /^HTTP\/1.1 101/);
                this.buffer = this.buffer.subarray(end + 4); this.upgraded = true;
            }
            while (this.buffer.length >= 2) {
                const opcode = this.buffer[0] & 15;
                let length = this.buffer[1] & 127; let offset = 2;
                if (length === 126) {
                    if (this.buffer.length < 4) return;
                    length = this.buffer.readUInt16BE(2); offset = 4;
                } else if (length === 127) {
                    if (this.buffer.length < 10) return;
                    length = Number(this.buffer.readBigUInt64BE(2)); offset = 10;
                }
                if (this.buffer.length < offset + length) return;
                const body = this.buffer.subarray(offset, offset + length);
                this.buffer = this.buffer.subarray(offset + length);
                if (opcode === 1) this.dispatchEvent(new MessageEvent('message', { data: body.toString() }));
                if (opcode === 8) this.close();
            }
        });
    }
    send(text) {
        const payload = Buffer.from(text); const mask = randomBytes(4);
        const header = Buffer.alloc(payload.length < 126 ? 2 : 4);
        header[0] = 0x81;
        if (payload.length < 126) header[1] = 0x80 | payload.length;
        else { header[1] = 0x80 | 126; header.writeUInt16BE(payload.length, 2); }
        const encoded = Buffer.from(payload);
        for (let index = 0; index < encoded.length; index++) encoded[index] ^= mask[index % 4];
        this.socket.write(Buffer.concat([header, mask, encoded]));
    }
    close() { this.socket.destroy(); }
}

const root = new URL('../../', import.meta.url);
const fixture = JSON.parse(await readFile(new URL('storage/app/tv4-integration-fixture.json', root), 'utf8'));
assert.match(fixture.database, /^toyota_showroom_tv4_test_\d+$/);
const envText = await readFile(new URL('.env.tv4-testing', root), 'utf8');
assert.match(envText, /DB_DATABASE=toyota_showroom_tv4_test_\d+/);
const key = envText.match(/^REVERB_APP_KEY=(.+)$/m)[1].trim();
const base = 'http://127.0.0.1:8001';
let id = fixture.inquiry_id;
let url = `/yeu-cau/${id}/tin-nhan`;
const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const sockets = [];
let queueWorker;
const fenceProcesses = new Set();
function fenceProcess(args) {
    const child = spawn('php', ['scripts/testing/tv4-publication-mysql.php', ...args.map(String)], { cwd: root, stdio: ['pipe', 'pipe', 'ignore'] });
    fenceProcesses.add(child);
    let output = ''; let exited = false;
    child.stdout.on('data', (chunk) => { output += chunk.toString(); });
    const completion = new Promise((resolve, reject) => {
        child.once('error', reject);
        child.once('exit', (code) => { exited = true; fenceProcesses.delete(child); code === 0 ? resolve() : reject(new Error(`Fence fixture exited ${code}`)); });
    });
    // Keep early process failures observed while waiting for a barrier.
    completion.catch(() => {});
    return { child, completion, output: () => output, exited: () => exited,
        async until(token) {
            const deadline = performance.now() + 8000;
            while (!output.includes(token)) {
                assert.ok(!exited && performance.now() < deadline, `Missing fence barrier ${token}`);
                await pause(10);
            }
            return output;
        } };
}
async function stopWorker() {
    if (!queueWorker) return;
    const worker = queueWorker; queueWorker = null;
    await new Promise((resolve) => { worker.once('exit', resolve); worker.kill(); });
}
async function flushQueue() {
    if (queueWorker) return;
    await new Promise((resolve, reject) => {
        const child = spawn('php', ['artisan', 'queue:work', '--env=tv4-testing', '--tries=1', '--stop-when-empty', '--sleep=0'], { cwd: root, stdio: 'ignore' });
        child.on('error', reject);
        child.on('exit', (code) => code === 0 ? resolve() : reject(new Error(`Test queue worker exit ${code}`)));
    });
}

class Session {
    cookies = new Map();
    csrf = '';
    async request(path, { method = 'GET', data, json = true } = {}) {
        const response = await fetch(`${base}${path}`, {
            method, redirect: 'manual', headers: {
                ...(json ? { Accept: 'application/json' } : {}),
                Cookie: [...this.cookies].map(([name, value]) => `${name}=${value}`).join('; '),
                ...(data ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf } : {}),
            }, body: data ? JSON.stringify(data) : undefined,
        });
        for (const cookie of response.headers.getSetCookie()) {
            const pair = cookie.split(';', 1)[0];
            const offset = pair.indexOf('=');
            this.cookies.set(pair.slice(0, offset), pair.slice(offset + 1));
        }
        const text = await response.text();
        const body = response.headers.get('content-type')?.includes('application/json') ? JSON.parse(text) : text;
        return { status: response.status, body };
    }
    async login(user) {
        const form = await this.request('/dang-nhap', { json: false });
        assert.equal(form.status, 200);
        this.csrf = form.body.match(/name="_token" value="([^"]+)"/)[1];
        const login = await this.request('/dang-nhap', { method: 'POST', data: { email: user.email, password: fixture.password } });
        assert.equal(login.status, 302, `Login failed: ${JSON.stringify(login.body)}`);
        const account = await this.request('/tai-khoan', { json: false });
        assert.equal(account.status, 200);
        this.csrf = account.body.match(/name="csrf-token" content="([^"]+)"/)[1];
    }
}

async function subscribe(session, version) {
    const ws = new LocalWebSocket(`/app/${key}?protocol=7&client=js&version=8.4.0&flash=false`);
    sockets.push(ws);
    const received = [];
    const channel = `private-inquiry.${id}.v${version}`;
    await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('WebSocket subscription timed out')), 8000);
        ws.addEventListener('error', reject);
        ws.addEventListener('message', async ({ data }) => {
            try {
                const event = JSON.parse(data);
                if (event.event === 'pusher:connection_established') {
                    const connection = JSON.parse(event.data);
                    const auth = await session.request('/broadcasting/auth', {
                        method: 'POST', data: { socket_id: connection.socket_id, channel_name: channel },
                    });
                    assert.equal(auth.status, 200);
                    ws.send(JSON.stringify({ event: 'pusher:subscribe', data: { ...auth.body, channel } }));
                } else if (event.event === 'pusher_internal:subscription_succeeded') {
                    clearTimeout(timer); resolve();
                } else if (event.event === 'message.created') {
                    received.push({ ...JSON.parse(event.data), receivedAt: performance.now(), channel: event.channel });
                } else if (event.event === 'pusher:ping') {
                    ws.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
                }
            } catch (error) { clearTimeout(timer); reject(error); }
        });
    });
    return { ws, received };
}

async function waitMessage(client, messageId) {
    const deadline = performance.now() + 4000;
    while (performance.now() < deadline) {
        const found = client.received.find((message) => message.id === messageId);
        if (found) return found;
        await pause(20);
    }
    throw new Error(`Missing realtime message ${messageId}`);
}

try {
    // Latency target uses a running worker, as in the documented development setup.
    queueWorker = spawn('php', ['artisan', 'queue:work', '--env=tv4-testing', '--tries=1', '--sleep=1'], { cwd: root, stdio: 'ignore' });
    const customer = new Session(); const sales = new Session(); const admin = new Session(); const replacement = new Session();
    await customer.login(fixture.users.customer);
    await sales.login(fixture.users.replacement); // MySQL concurrency run assigned this user.
    await replacement.login(fixture.users.sales);
    await admin.login(fixture.users.admin);
    const guest = new Session();
    const publicForm = { name: 'Khach test', phone: '0900000000', content: 'Tu van Toyota qua AJAX', customer_id: fixture.users.admin.id };
    const noCsrf = await guest.request('/tu-van', { method: 'POST', data: publicForm });
    assert.equal(noCsrf.status, 419);
    const guestForm = await guest.request('/dang-nhap', { json: false });
    guest.csrf = guestForm.body.match(/name="_token" value="([^"]+)"/)[1];
    const guestInquiry = await guest.request('/tu-van', { method: 'POST', data: publicForm });
    assert.equal(guestInquiry.status, 201);
    const created = await customer.request('/tu-van', { method: 'POST', data: publicForm });
    assert.equal(created.status, 201);
    const invalid = await guest.request('/tu-van', { method: 'POST', data: { ...publicForm, phone: '' } });
    assert.equal(invalid.status, 422);
    const throttled = await guest.request('/tu-van', { method: 'POST', data: publicForm });
    assert.equal(throttled.status, 429);
    id = created.body.id; url = `/yeu-cau/${id}/tin-nhan`;
    const assigned = await admin.request(`/admin/inquiries/${id}/assign`, { method: 'PATCH', data: { assigned_to: fixture.users.replacement.id } });
    assert.equal(assigned.status, 200);
    const start = await sales.request(`/staff/inquiries/${id}/start`, { method: 'PATCH', data: {} });
    assert.equal(start.status, 200);
    console.log('PASS real public AJAX/CSRF/422/429; customer creates own inquiry, admin assigns, sales starts');
    const inquiry = await customer.request(`/yeu-cau/${id}`);
    assert.equal(inquiry.status, 200);
    let version = inquiry.body.access_version;
    const first = await subscribe(customer, version);
    const second = await subscribe(sales, version);
    const payload = { content: `Realtime ${randomUUID()}`, client_message_id: randomUUID() };
    const started = performance.now();
    const sent = await customer.request(url, { method: 'POST', data: payload });
    assert.equal(sent.status, 201);
    await flushQueue();
    const [a, b] = await Promise.all([waitMessage(first, sent.body.id), waitMessage(second, sent.body.id)]);
    const latency = Math.max(a.receivedAt, b.receivedAt) - started;
    assert.ok(latency < 2000, `Realtime latency ${latency.toFixed(0)}ms exceeds demo target`);
    assert.equal(a.content, payload.content);
    assert.equal('internal_note' in a, false);
    console.log(`PASS two authenticated WebSocket sessions received persisted message in ${latency.toFixed(0)}ms`);
    const retry = await customer.request(url, { method: 'POST', data: payload });
    assert.equal(retry.status, 200); assert.equal(retry.body.id, sent.body.id);
    await pause(1200);
    assert.equal(first.received.filter((message) => message.id === sent.body.id).length, 1);
    const conflict = await customer.request(url, { method: 'POST', data: { ...payload, content: 'different' } });
    assert.equal(conflict.status, 409);
    console.log('PASS retry is idempotent, no duplicate broadcast, different payload returns 409');

    first.ws.close();
    const offline = await sales.request(url, { method: 'POST', data: { content: 'Offline catch-up', client_message_id: randomUUID() } });
    assert.equal(offline.status, 201);
    await flushQueue();
    const reconnected = await subscribe(customer, version);
    const catchup = await customer.request(`${url}?after_id=${sent.body.id}`);
    assert.equal(catchup.status, 200);
    assert.ok(catchup.body.data.some((message) => message.id === offline.body.id));
    console.log('PASS reconnect subscribes before scoped history catch-up and recovers offline message');

    await stopWorker();
    const queuedOld = await customer.request(url, { method: 'POST', data: { content: 'Queued before transfer', client_message_id: randomUUID() } });
    assert.equal(queuedOld.status, 201);
    const assignment = await admin.request(`/admin/inquiries/${id}/assign`, { method: 'PATCH', data: { assigned_to: fixture.users.sales.id } });
    assert.equal(assignment.status, 200); version += 1;
    assert.equal(assignment.body.access_version, version);
    await flushQueue();
    await pause(400);
    assert.ok(!second.received.some((message) => message.id === queuedOld.body.id));
    console.log('PASS real queued job created before reassignment is discarded at execution');
    const oldHttp = await sales.request(url, { method: 'POST', data: { content: 'denied', client_message_id: randomUUID() } });
    assert.equal(oldHttp.status, 403);
    const oldAuth = await sales.request('/broadcasting/auth', { method: 'POST', data: { socket_id: '123.456', channel_name: `private-inquiry.${id}.v${version - 1}` } });
    assert.equal(oldAuth.status, 403);
    const current = await subscribe(customer, version);
    const currentSales = await subscribe(replacement, version);
    const next = await replacement.request(url, { method: 'POST', data: { content: 'New assignment only', client_message_id: randomUUID() } });
    assert.equal(next.status, 201);
    await flushQueue();
    await Promise.all([waitMessage(current, next.body.id), waitMessage(currentSales, next.body.id)]);
    await pause(1200);
    assert.ok(!second.received.some((message) => message.id === next.body.id));
    assert.ok(!reconnected.received.some((message) => message.id === next.body.id));
    console.log('PASS reassignment revokes old HTTP/channel and old live sockets receive no new-version message');

    const queuedLock = await customer.request(url, { method: 'POST', data: { content: 'Queued before lock', client_message_id: randomUUID() } });
    assert.equal(queuedLock.status, 201);
    const lock = await admin.request(`/admin/users/${fixture.users.sales.id}/status`, { method: 'PATCH', data: { is_active: false } });
    assert.equal(lock.status, 302);
    await flushQueue();
    await pause(400);
    assert.ok(!current.received.some((message) => message.id === queuedLock.body.id));
    assert.ok(!currentSales.received.some((message) => message.id === queuedLock.body.id));
    console.log('PASS real queued job created before H02 lock is discarded; old sockets receive nothing');
    const locked = await replacement.request(url);
    assert.equal(locked.status, 403);
    const deniedChannel = await replacement.request('/broadcasting/auth', { method: 'POST', data: { socket_id: '123.456', channel_name: `private-inquiry.${id}.v${version}` } });
    assert.ok([401, 403].includes(deniedChannel.status));
    console.log('PASS H02 account lock revokes authenticated session and private-channel authorization');

    const close = await admin.request(`/admin/inquiries/${id}/close`, { method: 'PATCH', data: { close_result: 'da_tu_van' } });
    assert.equal(close.status, 200); assert.equal(close.body.status, 'closed');
    const closedSend = await customer.request(url, { method: 'POST', data: { content: 'denied after close', client_message_id: randomUUID() } });
    assert.equal(closedSend.status, 403);
    console.log('PASS complete assigned/in-progress/closed lifecycle; closed chat is read-only');

    // Actual Reverb publication held at the original final-SELECT boundary (no row transaction).
    // A separate PHP revoker starts while the publisher is paused and must NOT commit yet.
    for (const scenario of ['reassign', 'lock-sales', 'lock-customer']) {
        for (const user of [fixture.users.sales, fixture.users.customer]) {
            const unlock = await admin.request(`/admin/users/${user.id}/status`, { method: 'PATCH', data: { is_active: true } });
            assert.equal(unlock.status, 302);
        }
        const ownerSession = new Session(); const assignedSession = new Session();
        await ownerSession.login(fixture.users.customer); await assignedSession.login(fixture.users.sales);
        const prepare = fenceProcess(['fixture', fixture.users.customer.id, fixture.users.sales.id, fixture.users.admin.id]);
        await prepare.completion;
        const prepared = JSON.parse(prepare.output());
        id = prepared.id; url = `/yeu-cau/${id}/tin-nhan`;
        const ownerSocket = await subscribe(ownerSession, prepared.version);
        const assignedSocket = await subscribe(assignedSession, prepared.version);
        const publisher = fenceProcess(['publish', fixture.users.customer.id, id]);
        const boundary = await publisher.until('BOUNDARY');
        const messageId = JSON.parse(boundary.trim().split('\n').find((line) => line.includes('BOUNDARY'))).message_id;
        const target = scenario === 'reassign' ? fixture.users.replacement.id
            : scenario === 'lock-sales' ? fixture.users.sales.id : fixture.users.customer.id;
        const revoker = fenceProcess(['revoke', scenario, fixture.users.admin.id, id, target]);
        await revoker.until('START'); await pause(250);
        assert.equal(revoker.exited(), false);
        assert.equal(revoker.output().includes('COMMITTED'), false);
        publisher.child.stdin.end('PUBLISH\n');
        await publisher.completion;
        assert.ok(publisher.output().includes('PUBLISHED'));
        await Promise.all([waitMessage(ownerSocket, messageId), waitMessage(assignedSocket, messageId)]);
        await revoker.completion;
        assert.ok(revoker.output().includes('COMMITTED'));
        const denied = scenario === 'lock-customer' ? ownerSession : assignedSession;
        const deniedWrite = await denied.request(url, { method: 'POST', data: { content: 'Must deny after revocation', client_message_id: randomUUID() } });
        assert.equal(deniedWrite.status, 403);
        await flushQueue(); // Original intent remains; replay after revocation must be discarded.
        await pause(300);
        assert.equal(ownerSocket.received.filter((message) => message.id === messageId).length, 1);
        assert.equal(assignedSocket.received.filter((message) => message.id === messageId).length, 1);
        if (scenario === 'reassign') {
            const newSales = new Session(); await newSales.login(fixture.users.replacement);
            const detail = await newSales.request(`/staff/inquiries/${id}`);
            assert.equal(detail.status, 200);
            assert.equal(detail.body.access_version, prepared.version + 1);
            await subscribe(newSales, prepared.version + 1);
        }
        console.log(`PASS real Reverb fence ${scenario}: independent revoker waited at SELECT/publish boundary; two sockets received; post-commit stale intent discarded`);
    }
} finally {
    fenceProcesses.forEach((child) => child.kill());
    await stopWorker();
    sockets.forEach((ws) => ws.close());
}
