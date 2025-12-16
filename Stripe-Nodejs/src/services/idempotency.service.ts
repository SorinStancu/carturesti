
import { EventRepository } from '../repositories/event.repository';

const LOCK_TTL_MS = 10000;

export class IdempotencyService {
  private locks = new Set<string>();

  constructor(private eventRepository: EventRepository) {}

  async isEventProcessed(eventId: string): Promise<boolean> {
    return this.eventRepository.exists(eventId);
  }

  async markEventAsProcessed(eventId: string, type: string): Promise<void> {
    await this.eventRepository.create(eventId, type);
  }

  async acquireLock(eventId: string): Promise<boolean> {
    if (this.locks.has(eventId)) {
      return false;
    }
    this.locks.add(eventId);
    setTimeout(() => this.locks.delete(eventId), LOCK_TTL_MS);
    return true;
  }

  releaseLock(eventId: string): void {
    this.locks.delete(eventId);
  }
}
