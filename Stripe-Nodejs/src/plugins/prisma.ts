
import fp from 'fastify-plugin';
import { FastifyPluginAsync } from 'fastify';
import { PrismaClient } from '@prisma/client';

// Folosim 'augmentarea modulului' pentru a adauga proprietatea prisma la instanta Fastify
declare module 'fastify' {
  interface FastifyInstance {
    prisma: PrismaClient;
  }
}

const prismaPlugin: FastifyPluginAsync = fp(async (server, options) => {
  const prisma = new PrismaClient();

  await prisma.$connect();

  // Decoram instanta fastify cu clientul prisma
  // Acesta este un pattern comun in Fastify pentru a face DB disponibila global
  server.decorate('prisma', prisma);

  server.addHook('onClose', async (server) => {
    await server.prisma.$disconnect();
  });
});

export default prismaPlugin;
