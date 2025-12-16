
import Stripe from 'stripe';
import { OrderService, OrderStatus } from './order.service';

export async function processCheckoutSession(
  session: Stripe.Checkout.Session,
  orderService: OrderService
): Promise<void> {

  const paymentStatus = session.payment_status;
  const clientReferenceId = session.client_reference_id;
  const metadataOrderId = session.metadata?.order_id;
  
  const orderId = clientReferenceId || metadataOrderId;

  if (paymentStatus === 'paid' && orderId) {
    const parsedId = parseInt(String(orderId), 10);
    if (!Number.isNaN(parsedId)) {
      await orderService.updateOrderStatus(parsedId, OrderStatus.PAYING);
    }
  } else {
    // no-op
  }
}
