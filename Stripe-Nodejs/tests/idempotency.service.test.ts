import assert from 'node:assert/strict';
import { IdempotencyService } from '../src/services/idempotency.service';

class MockEventRepository {
  private ids = new Set<string>();
  async exists(id: string): Promise<boolean> {
    return this.ids.has(id);
  }
  async create(id: string, type: string): Promise<void> {
    this.ids.add(id);
  }
}

async function testIdempotencyPersistenceAndLocks() {
  const repo = new MockEventRepository();
  const service = new IdempotencyService(repo as any);

  const id = 'evt_1';
  const before = await service.isEventProcessed(id);
  assert.equal(before, false);

  await service.markEventAsProcessed(id, 'checkout.session.completed');
  const after = await service.isEventProcessed(id);
  assert.equal(after, true);

  const lock1 = await service.acquireLock('evt_lock');
  assert.equal(lock1, true);
  const lock2 = await service.acquireLock('evt_lock');
  assert.equal(lock2, false);
  service.releaseLock('evt_lock');
  const lock3 = await service.acquireLock('evt_lock');
  assert.equal(lock3, true);
}

async function run() {
  await testIdempotencyPersistenceAndLocks();
  console.log('IdempotencyService tests passed');
}

run();
