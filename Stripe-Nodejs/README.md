
# Stripe Integration Node.js (Fastify + Prisma)
Implementare plata Stripe in Node.js folosind framework-ul Fastify si Prisma ORM. Arhitectura este gandita modular, respectand principiile SOLID si design pattern-uri moderne.

## 🛠 Detalii Tehnice

### Tech Stack
- **Runtime**: Node.js
- **Framework Web**: Fastify (pentru performanta ridicata si overhead redus)
- **Limbaj**: TypeScript (pentru siguranta tipurilor)
- **ORM**: Prisma (PostgreSQL)
- **Stripe SDK**: `stripe-node` cu TypeScript definitions stricte

### Componente Stripe Utilizate
Proiectul se concentreaza pe integrarea **Stripe Checkout** pentru plati unice (si potential abonamente), incluzand:
- **Checkout Sessions**: Crearea si gestionarea sesiunilor de plata securizate, gazduite de Stripe.
- **Webhooks**: Ascultarea activa a evenimentelor asincrone trimise de Stripe pentru a confirma platile si a actualiza starea comenzilor.

### Arhitectura Bazei de Date & Abonamente
Modelarea datelor este realizata prin Prisma Schema (`schema.prisma`):
- **Orders**: Stocheaza informatiile despre comenzi, inclusiv statusul (`pending`, `paying`, `paid`, `failed`). Aceasta serveste ca sursa de adevar pentru drepturile de acces/livrare. Modificarile de status sunt declansate exclusiv de evenimente verificate.
- **StripeEvents**: Un tabel dedicat pentru stocarea ID-urilor evenimentelor procesate (jurnal de idempotenta), esential pentru a evita procesarea dubla.

### Gestionarea Webhook-urilor
Sistemul de webhook-uri este punctul central al integrarii si este construit pentru rezilienta:
1.  **Validare Semnatura**: Toate cererile sunt validate criptografic folosind cheia secreta a webhook-ului (`STRIPE_WEBHOOK_SECRET`) si semnatura `Stripe-Signature` din header, asigurand ca cererea vine intr-adevar de la Stripe.
2.  **Idempotency**: Fiecare `event_id` primit este verificat in baza de date (`EventRepository`). Daca exista deja, cererea este marcata imediat ca `received` fara a mai executa logica de business, prevenind efecte secundare nedorite (ex: livrarea produsului de doua ori).
3.  **Gestionarea Erorilor**: Erorile sunt prinse si logate detaliat. In cazul unei erori critice de procesare, serverul returneaza un status 500, semnalizand Stripe sa reincerce trimiterea webhook-ului (mecanismul nativ de retry exponential al Stripe).
4.  **Handlers Map (Strategy)**: Ruta webhook foloseste un map de handler-e per tip de eveniment Stripe pentru extensibilitate si separare clara a responsabilitatilor. Evenimentul `checkout.session.completed` este procesat prin functia `processCheckoutSession`.

## 🚀 Provocari & Solutii

### Probleme de Concurenta (Race Conditions)
O provocare majora in sistemele distribuite este primirea aceluiasi webhook de mai multe ori simultan sau executia paralela care poate duce la stari inconsistente.
*   **Solutie**: Am implementat un mecanism de **Locking (Blocare)**.
    *   Am folosit un **IdempotencyService** care, pe langa verificarea in baza de date, mentine si un lock temporar in memorie (sau Redis in productie) pentru ID-ul evenimentului curent.
    *   Daca doua request-uri pentru acelasi eveniment ajung in aceeasi milisecunda, doar unul obtine lock-ul si proceseaza comanda, celalalt este respins politicos (status 200) sau asteapta.

### Securitatea si Type-Safety
Lucrul cu date financiare necesita o strictete maxima.
*   **Solutie**: Utilizarea **TypeScript** si a **DTO-urilor** (Data Transfer Objects) implicite din SDK-ul Stripe. Validarea `rawBody` este stricta, iar configurarea Fastify a fost ajustata pentru a pastra buffer-ul original necesar verificarii semnaturii, fara a compromite procesarea JSON pentru restul aplicatiei.
*   **Practici aditionale**: `processCheckoutSession` primeste explicit `OrderService` prin dependency injection si foloseste `OrderStatus` enum pentru tip-safety si evitarea string-urilor magice; `orderId` este convertit in mod sigur la numar inainte de update.

### Decuplarea Logicii (Separation of Concerns)
Evitarea unui "Controller masiv".
*   **Solutie**: Am adoptat **Repository Pattern** si **Service Layer**.
    *   `WebhookController` (acum ruta) doar coordoneaza fluxul HTTP.
    *   `OrderService` contine logica de business.
    *   `EventRepository` si `OrderRepository` gestioneaza exclusiv interactiunea cu baza de date.
    *   TTL pentru lock este extras ca constanta (`LOCK_TTL_MS`) in `IdempotencyService`, oferind control predictibil pentru conditiile de cursa.

## Testare

- `tests/idempotency.service.test.ts`: verifica persistenta evenimentelor procesate si comportamentul lock-urilor (acquire, release, double acquire).
- `tests/checkout-processor.test.ts`: verifica scenarii pentru `processCheckoutSession` cand plata este `paid` cu `client_reference_id` sau `metadata.order_id` si cazurile fara actualizare.
- `tests/order.service.test.ts`: verifica actualizarea statusului pentru comenzi `pending`, evitarea actualizarilor pentru `paid`, si comportamentul cand comanda lipseste.




