# Progetto Laravel

## Requisiti

- PHP 8.2+
- Composer
- MySQL o un altro database supportato
- Node.js e npm (per la gestione degli asset)
- Docker e Docker Compose

## Configurazione dell'Ambiente di Sviluppo

1. **Clona il repository:**

   ```bash
   git clone https://github.com/mattia-cavalli/laravel-api.git
   cd laravel-api
   ```

2. **Installa le dipendenze PHP:**

   Assicurati di avere Composer installato, quindi esegui:

   ```bash
   composer install
   ```

3. **Configura il file `.env`:**

   Copia il file `.env.example` e rinominalo in `.env`. Modifica le seguenti variabili secondo le tue esigenze:

   ```plaintext
   APP_NAME=Laravel
   APP_ENV=local
   APP_KEY=base64:...
   APP_DEBUG=true
   APP_URL=http://localhost

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tuo_database
   DB_USERNAME=tuo_utente
   DB_PASSWORD=tuo_password
   ```

4. **Genera la chiave dell'applicazione:**

   ```bash
   php artisan key:generate
   ```

5. **Migra il database:**

   Assicurati che il tuo database sia in esecuzione e poi esegui:

   ```bash
   php artisan migrate
   ```

6. **Installa le dipendenze JavaScript:**

   Assicurati di avere Node.js e npm installati, quindi esegui:

   ```bash
   npm install
   npm run dev
   ```

## Esecuzione del Progetto

Per avviare il server di sviluppo, esegui:

```bash
php artisan serve
```

## Esecuzione con Docker

1. **Assicurati di avere Docker e Docker Compose installati.**

2. **Costruisci e avvia i container:**

   ```bash
   docker compose up -d
   ```

3. **Accedi al container dell'applicazione:**

   ```bash
   docker compose exec app bash
   ```

4. **Esegui le migrazioni all'interno del container:**

   ```bash
   php artisan migrate
   ```

5. **Accedi al progetto:**

   Il progetto sarà disponibile all'indirizzo [http://localhost](http://localhost).

## Test

Per eseguire i test, esegui:

```bash
php artisan test
```

Se stai usando Docker, puoi eseguire i test all'interno del container:

```bash
docker compose exec app php artisan test
```