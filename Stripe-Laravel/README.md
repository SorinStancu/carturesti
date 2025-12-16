# Implementare Plati Stripe

## Prezentare generala
Implementare plati Stripe. 
Codul este scris pentru o arhitectura scalabila, folosind Services, Actions, DTO-uri/Enums si Procesare Asincrona (cozi).

## Imbunatatiri Arhitecturale

### 1. Pattern-ul Service Layer
**Locatie:** `Stripe\Services\StripeService`
- **Scop:** Decupleaza logica aplicatiei de SDK-ul PHP Stripe.
- **Beneficiu:** Permite testarea unitara mai usoara.  Daca SDK-ul se schimba, actualizarile sunt necesare intr-un singur loc.

### 2. Pattern-ul Action (Responsabilitate Unica)
**Locatie:** `Stripe\Actions\HandleCheckoutSessionCompletedAction`
- **Scop:** Incapsuleaza logica de business specifica pentru gestionarea unei plati reusite.
- **Beneficiu:** Pastreaza Controller-ele cu cat mai putin cod. Logica pentru "ce se intampla dupa plata" este izolata si reutilizabila.

### 3. Procesarea Asincrona a Evenimentelor (Cozi/Jobs)
**Locatie:** `Stripe\Jobs\ProcessStripeWebhookJob`
- **Scop:** Gestionarea webhook-urilor este sensibila la timp. In loc sa procesam logica sincron, evenimentele sunt trimise intr-o coada.
- **Beneficiu:**
    - Returneaza imediat `200 OK` catre Stripe, prevenind timeout-urile.
    - Creste rezilienta sistemului; daca procesarea esueaza, job-ul poate fi reincercat automat fara ca Stripe sa fie nevoit sa retrimita webhook-ul.

### 4. Idempotenta si Gestionarea Race Conditions
**Locatie:** `Stripe\Services\IdempotencyService`
- **Scop:** Previne procesarea duplicat a aceluiasi eveniment Stripe (prevenirea platilor duble).
- **Mecanism:** Foloseste lock-uri atomice si cache pentru a urmari ID-urile evenimentelor procesate (`evt_...`). Chiar daca Stripe trimite webhook-ul de doua ori (de ex. din cauza latentei retelei), sistemul garanteaza comanda unica.

### 5. Type Safety cu Enums
**Locatie:** `Stripe\Enums\StripeEventType`, `Stripe\Enums\PaymentStatus`
- **Scop:** Inlocuieste "magic strings" cu Enumerari tipizate (functionalitati PHP 8.1+).
- **Beneficiu:** Reduce erorile.

## Descrierea Fluxului de Lucru

1.  **Cerere de Plata:** `Stripe\Driver\Channel` foloseste `StripeService` pentru a genera o Sesiune de Checkout si redirectioneaza utilizatorul.
2.  **Primire Webhook:** Stripe trimite un webhook catre `WebhookController`.
3.  **Validare:** Controller-ul valideaza semnatura folosind `StripeService`.
4.  **Verificare Idempotenta:** `IdempotencyService` verifica daca ID-ul evenimentului a fost deja gestionat.
5.  **Dispatch:** Evenimentele valide sunt trimise catre `ProcessStripeWebhookJob`.
6.  **Executie:** Job-ul ruleaza in fundal, invocand `HandleCheckoutSessionCompletedAction` pentru a actualiza statusul Comenzii in baza de date.

## Cerinte Tehnice
- PHP 8.1+
- Framework Laravel (drivere Queue & Cache configurate)
- Stripe PHP SDK


