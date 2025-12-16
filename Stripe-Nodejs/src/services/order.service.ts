
import { OrderRepository } from '../repositories/order.repository';

export enum OrderStatus {
  PENDING = 'pending',
  PAYING = 'paying',
  PAID = 'paid',
  FAILED = 'failed',
}

export class OrderService {
  constructor(private orderRepository: OrderRepository) {}

  async updateOrderStatus(orderId: number, status: OrderStatus): Promise<void> {
    const order = await this.orderRepository.findById(orderId);
    
    if (!order) {
       console.warn(`Order ${orderId} not found.`);
       return;
    }

    if (order.status !== OrderStatus.PAID) {
      console.log(`[DB] Updating order ${orderId} status to ${status}`);
      await this.orderRepository.updateStatus(orderId, status);
    }
  }
}
