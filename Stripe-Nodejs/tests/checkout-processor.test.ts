import assert from 'node:assert/strict';
import { processCheckoutSession } from '../src/services/checkout-processor';
import { OrderStatus } from '../src/services/order.service';

class MockOrderService {
  lastId: number | null = null;
  lastStatus: OrderStatus | null = null;
  async updateOrderStatus(orderId: number, status: OrderStatus): Promise<void> {
    this.lastId = orderId;
    this.lastStatus = status;
  }
}

async function testPaidWithClientReferenceId() {
  const svc = new MockOrderService();
  const session: any = {
    id: 'cs_1',
    payment_status: 'paid',
    client_reference_id: '42',
    metadata: {},
  };
  await processCheckoutSession(session, svc as any);
  assert.equal(svc.lastId, 42);
  assert.equal(svc.lastStatus, OrderStatus.PAYING);
}

async function testPaidWithMetadataOrderId() {
  const svc = new MockOrderService();
  const session: any = {
    id: 'cs_2',
    payment_status: 'paid',
    client_reference_id: null,
    metadata: { order_id: '7' },
  };
  await processCheckoutSession(session, svc as any);
  assert.equal(svc.lastId, 7);
  assert.equal(svc.lastStatus, OrderStatus.PAYING);
}

async function testNotPaidOrMissingId() {
  const svc = new MockOrderService();
  const session1: any = {
    id: 'cs_3',
    payment_status: 'unpaid',
    client_reference_id: '1',
    metadata: {},
  };
  await processCheckoutSession(session1, svc as any);
  assert.equal(svc.lastId, null);

  const session2: any = {
    id: 'cs_4',
    payment_status: 'paid',
    client_reference_id: null,
    metadata: {},
  };
  await processCheckoutSession(session2, svc as any);
  assert.equal(svc.lastId, null);
}

async function run() {
  await testPaidWithClientReferenceId();
  await testPaidWithMetadataOrderId();
  await testNotPaidOrMissingId();
  console.log('checkout-processor tests passed');
}

run();
