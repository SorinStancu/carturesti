
import { FastifyInstance, FastifyPluginAsync } from 'fastify';
import { stripe } from '../lib/stripe';
import { config } from '../config/env';
import Stripe from 'stripe';
import { processCheckoutSession } from '../services/checkout-processor';
import { OrderRepository } from '../repositories/order.repository';
import { EventRepository } from '../repositories/event.repository';
import { OrderService } from '../services/order.service';
import { IdempotencyService } from '../services/idempotency.service';

const webhookRoutes: FastifyPluginAsync = async (fastify: FastifyInstance) => {

  // Instantiere servicii cu dependinte (Dependency Injection manual)
  
  const orderRepo = new OrderRepository(fastify.prisma);
  const eventRepo = new EventRepository(fastify.prisma);
  
  const orderService = new OrderService(orderRepo);
  const idempotencyService = new IdempotencyService(eventRepo);

  fastify.post('/webhook', {
    config: {
      rawBody: true,
    },
  }, async (req, reply) => {      

      const sig = req.headers['stripe-signature'];
      const body = req.rawBody as Buffer;

      if (!sig || !body) {
        req.log.error('Lipseste semnatura Stripe sau corpul cererii');
        return reply.status(400).send('Eroare Webhook: Lipseste semnatura sau corpul');
      }

      let event: Stripe.Event;

      try {
        event = stripe.webhooks.constructEvent(body, sig, config.stripe.webhookSecret);
      } catch (err: any) {
        req.log.error(`Verificarea semnaturii webhook a esuat: ${err.message}`);
        return reply.status(400).send(`Eroare Webhook: ${err.message}`);
      }

      const { id: eventId, type: eventType } = event;

      // Idempotency
      if (await idempotencyService.isEventProcessed(eventId)) {
         req.log.info(`Evenimentul ${eventId} deja procesat.`);
         return reply.status(200).send({ received: true });
      }

      const locked = await idempotencyService.acquireLock(eventId);
      if (!locked) {
         return reply.status(200).send({ received: true });
      }

      try {
        const handlers: Record<string, (e: Stripe.Event) => Promise<void>> = {
          'checkout.session.completed': async (e: Stripe.Event) => {
            const session = e.data.object as Stripe.Checkout.Session;
            await processCheckoutSession(session, orderService);
          },
        };

        const handler = handlers[eventType];
        if (handler) {
          await handler(event);
        } else {
          req.log.info(`Tip eveniment netratat ${eventType}`);
        }

        await idempotencyService.markEventAsProcessed(eventId, eventType);
        return reply.status(200).send({ received: true });

      } catch (err: any) {
        req.log.error(`Eroare procesare eveniment ${eventId}: ${err.message}`);
        return reply.status(500).send('Eroare Interna Server');
      } finally {
        idempotencyService.releaseLock(eventId);
      }
  });
};

export default webhookRoutes;
