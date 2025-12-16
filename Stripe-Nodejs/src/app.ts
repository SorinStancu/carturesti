
import Fastify, { FastifyInstance } from 'fastify';
import fRawBody from 'fastify-raw-body';
import prismaPlugin from './plugins/prisma';
import webhookRoutes from './routes/webhook.routes';
import { config } from './config/env';

const server: FastifyInstance = Fastify({
  logger: true,
});

const start = async () => {
  try {
           
    //  Core plugins
    await server.register(fRawBody, {
      field: 'rawBody',
      global: false,
      encoding: false,
      runFirst: true,
    });

    //  Database plugin
    await server.register(prismaPlugin);

    //  Domain Routes
    await server.register(webhookRoutes);

    // Health check
    server.get('/health', async () => {
      return { status: 'ok' };
    });

    await server.listen({ port: config.server.port, host: config.server.host });
    console.log(`Server asculta la http://${config.server.host}:${config.server.port}`);
  } catch (err) {
    server.log.error(err);
    process.exit(1);
  }
};

start();
