
import Stripe from 'stripe';
import { config } from '../config/env';

// Instanta client Stripe Singleton
export const stripe = new Stripe(config.stripe.secretKey, {
  apiVersion: '2024-11-20.acacia', // Cea mai buna practica: fixati versiunea API
  typescript: true,
  appInfo: {
    name: 'Stripe Node.js Integration',
    version: '1.0.0',
  },
});
