
import { PrismaClient } from '@prisma/client';

export class EventRepository {
  constructor(private prisma: PrismaClient) {}

  async findById(id: string) {
    return this.prisma.stripeEvent.findUnique({
      where: { id },
    });
  }

  async create(id: string, type: string) {
    return this.prisma.stripeEvent.create({
      data: {
        id,
        type,
        processed: true,
      },
    });
  }
  
  // Metoda pentru a verifica existenta (utila pentru idempotenta rapida)
  async exists(id: string): Promise<boolean> {
    const count = await this.prisma.stripeEvent.count({
      where: { id },
    });
    return count > 0;
  }
}
