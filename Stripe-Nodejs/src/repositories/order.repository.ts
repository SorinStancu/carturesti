
import { PrismaClient } from '@prisma/client';

export class OrderRepository {
  constructor(private prisma: PrismaClient) {}

  async findById(id: number) {
    return this.prisma.order.findUnique({
      where: { id },
    });
  }

  async updateStatus(id: number, status: string) {
    return this.prisma.order.update({
      where: { id },
      data: { status },
    });
  }
}
