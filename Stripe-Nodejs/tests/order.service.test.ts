import assert from 'node:assert/strict';
import { OrderService, OrderStatus } from '../src/services/order.service';

class MockOrderRepository {
  private store = new Map<number, { id: number; status: string }>();
  constructor() {
    this.store.set(1, { id: 1, status: 'pending' });
    this.store.set(2, { id: 2, status: 'paid' });
  }
  async findById(id: number) {
    return this.store.get(id) || null;
  }
  async updateStatus(id: number, status: string) {
    const o = this.store.get(id);
    if (o) this.store.set(id, { id, status });
  }
}

async function testUpdatesPendingToPaying() {
  const repo = new MockOrderRepository();
  const service = new OrderService(repo as any);
  await service.updateOrderStatus(1, OrderStatus.PAYING);
  const o = await repo.findById(1);
  assert.equal(o?.status, 'paying');
}

async function testSkipsIfAlreadyPaid() {
  const repo = new MockOrderRepository();
  const service = new OrderService(repo as any);
  await service.updateOrderStatus(2, OrderStatus.PAYING);
  const o = await repo.findById(2);
  assert.equal(o?.status, 'paid');
}

async function testNoUpdateIfMissing() {
  const repo = new MockOrderRepository();
  const service = new OrderService(repo as any);
  await service.updateOrderStatus(999, OrderStatus.PAYING);
  const o = await repo.findById(999);
  assert.equal(o, null);
}

async function run() {
  await testUpdatesPendingToPaying();
  await testSkipsIfAlreadyPaid();
  await testNoUpdateIfMissing();
  console.log('order.service tests passed');
}

run();
