import test from 'node:test';
import assert from 'node:assert/strict';
import { createSavedCarsStore, STORAGE_KEY } from '../../resources/js/modules/catalog/saved-cars-store.js';

function memoryStorage(initial = {}) {
    const data = new Map(Object.entries(initial));
    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, String(value)),
        removeItem: (key) => data.delete(key),
        dump: () => data.get(STORAGE_KEY),
    };
}

test('toggle saves, removes and persists ids across a new store (reload)', () => {
    const storage = memoryStorage();
    const store = createSavedCarsStore(storage, 3);
    assert.equal(store.toggle(5), 'added');
    assert.equal(store.toggle('7'), 'added');
    assert.equal(store.toggle(5), 'removed');
    assert.deepEqual(createSavedCarsStore(storage, 3).ids(), [7]);
});

test('limit is enforced and duplicates or junk are dropped when reading', () => {
    const storage = memoryStorage({ [STORAGE_KEY]: JSON.stringify([1, 1, '2', -3, 'x', 4.5, 3, 9, 10]) });
    const store = createSavedCarsStore(storage, 3);
    assert.deepEqual(store.ids(), [1, 2, 3]);
    assert.equal(store.toggle(11), 'full');
    assert.deepEqual(store.ids(), [1, 2, 3]);
});

test('corrupt JSON starts from an empty list without throwing', () => {
    const store = createSavedCarsStore(memoryStorage({ [STORAGE_KEY]: '{oops' }), 3);
    assert.deepEqual(store.ids(), []);
    assert.equal(store.toggle(1), 'added');
});

test('unavailable storage reports unavailable instead of throwing', () => {
    const blocked = { getItem() { throw new Error('denied'); }, setItem() { throw new Error('denied'); }, removeItem() {} };
    for (const storage of [blocked, null]) {
        const store = createSavedCarsStore(storage, 3);
        assert.equal(store.available, false);
        assert.deepEqual(store.ids(), []);
        assert.equal(store.toggle(1), 'unavailable');
    }
});

test('retain drops ids the server no longer shows', () => {
    const storage = memoryStorage({ [STORAGE_KEY]: '[4,8,15]' });
    const store = createSavedCarsStore(storage, 12);
    assert.equal(store.retain([15, 4]), 2);
    assert.equal(storage.dump(), '[4,15]');
});
