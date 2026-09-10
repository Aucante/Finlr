# Finlr

---

## 1. Prerequisites
PHP, Composer, Node.js and NPM run **inside the Sail container** (PHP 8.3) and don't need to be installed locally. They're only useful on the host if you prefer working outside Docker:

- PHP ≥ 8.3
- Node.js ≥ 20 and NPM
- PostgreSQL 15

---

## 2. Installation

```bash
# 1. Clone the repository and move into it
git clone <repo-url> finlr && cd finlr

# 2. Copy the environment file
cp .env.example .env
```

```bash
# 3. Install PHP dependencies via a temporary Composer container
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# 4. Start the Docker environment
./vendor/bin/sail up -d

# 5. Generate the application key and run migrations
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate

# 6. Install front-end dependencies and build the assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

> Tip: add an alias to shorten the commands used throughout the rest of this README:
> ```bash
> echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc && source ~/.bashrc
> ```

The application is available at **http://localhost**.

---

## 3. Starting the development environment

```bash
# Start the containers (Laravel + PostgreSQL)
sail up -d

# Compile assets in watch mode (Vite HMR)
sail npm run dev
```

To follow application logs live (Laravel Pail):

```bash
sail artisan pail
```

### Local S3 storage (MinIO)

`sail up -d` also starts a MinIO container that emulates S3 for local development. A `reports-bucket` bucket is created automatically (`minio-setup` service). `FILESYSTEM_DISK` is set to `s3` and already points at it in `.env.example`.

- API S3: http://127.0.0.1:9000
- Web console: http://127.0.0.1:9001 (login with `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` from `.env`)

### Local mail testing (Mailpit)

`sail up -d` also starts a [Mailpit](https://github.com/axllent/mailpit) container that catches every email sent by the app locally (`MAIL_MAILER=smtp` points at it in `.env.example`).

- Web UI: http://localhost:8025

Test mail :
```bash
sail artisan tinker --execute="Mail::raw('Test Mailpit', function(\$m) { \$m->to('test@example.com')->subject('Hello Mailpit'); });"
```

### Stripe webhooks (Stripe CLI)

`sail up -d` also starts a `stripe-cli` container that automatically forwards Stripe webhook events to `http://laravel.test:80/api/stripe/webhook`. Set `STRIPE_SECRET_KEY` in `.env` and the tunnel authenticates and starts on its own — no local Stripe CLI installation or `stripe login` needed.

Check logs
```bash
sail logs stripe-cli
```

Trigger a fake payment stripe conteneur
```bash
sail exec stripe-cli stripe trigger payment_intent.succeeded
```

Check payment on Laravel side
```bash
sail logs stripe-cli --tail 20
```
---

## 4. Running the automated tests

### Backend (PHPUnit)

```bash
# Full suite
sail artisan test

# A specific test file
sail artisan test tests/Feature/CalculatorPageTest.php

# A specific test by method name
sail artisan test --filter=test_home_page_injects_the_financial_configuration
```

### Frontend (Vitest + React Testing Library)

```bash
# Full suite
sail npm run test

# A specific test file
sail npm run test -- resources/js/test/Pages/Auth/Login.test.jsx
```

---

## 5. Key feature structure

- **Financial configuration** — `config/financial.php` centralizes the suggested tax rates per wrapper (PEA, CTO, AV) and the calculation engine's default values. These values are exposed on the PHP side via `App\Domain\Financial\FinancialSettings` and injected into the `Calculator.jsx` Inertia page (no value is hardcoded on the React side).
- **Internationalization (i18n)** — `react-i18next` is configured in `resources/js/i18n.js`. All UI labels (navbar, forms, KPIs, chart) are centralized in `lang/fr.json`, `lang/en.json` and `lang/it.json`; zero hardcoded text in the React components.
- **Dark Mode** — handled by the `resources/js/hooks/useDarkMode.js` hook, which persists the theme choice in `localStorage` and toggles the `dark` class on `<html>`. An inline script in `resources/views/app.blade.php` applies the theme before the first render to avoid any flash of unstyled content (FOUC).

---

## 6. Calculator Engine Architecture (Strategy Pattern)

This project uses the **Strategy Pattern** to decouple the core application logic from the underlying calculation engine. It supports two execution strategies out of the box:

1. **`PrivateCalculatorEngineAdapter` (Production / Default)**  
   Delegates complex financial calculations, tax rules, and inflation adjustments to the proprietary package [`saucante74/finlr-engine`](https://github.com/saucante74/FINLR-ENGINE).
2. **`DummyCalculatorEngine` (Fallback / Testing)**  
   A lightweight, zero-dependency local engine used when the private package is unavailable (e.g., open-source contributors, public CI/CD pipelines) or explicitly forced via configuration.


### How It Works Under the Hood

The application relies on Laravel's Service Container to dynamically bind `CalculatorEngineInterface`:

When `CalculatorEngineInterface` is injected into actions or controllers, the `CalculatorServiceProvider` determines which implementation to instantiate:
- If `saucante74\CalculatorEngine\CalculatorEngine` is loaded **AND** `CALCULATOR_FORCE_DUMMY` is `false` → Uses the **Private Package**.
- Otherwise → Silently falls back to the **Dummy Engine**.

