# Charte de Revue de Code & Audit — Finlr

Ce document définit les critères de qualité, de sécurité et d'architecture obligatoires pour toute contribution au projet. Tout code non conforme doit être bloqué lors des revues automatisées ou manuelles.

---

## 1. Sécurité & Données Sensibles
- **Zero Secret in Code :** Interdiction d'inclure des clés, tokens ou mots de passe en dur. Utiliser `.env` et mettre à jour `.env.example`.
- **Assainissement & XSS :**
    - Tout affichage HTML/PDF doit être échappé.
    - Interdiction d'afficher directement des données issues de `$_GET`, `$_POST` ou `$request->all()` sans validation préalable via une `FormRequest`.
- **Props Inertia :** Ne jamais sérialiser un modèle Eloquent complet dans les props Inertia. Extraire uniquement les champs nécessaires (principe de minimisation des données).
- **Autorisations :** Toute action modifiant l'état ou accédant à une capacité payante doit vérifier les droits côté serveur (Gate/Policy/Middleware).

---

## 2. Rigueur Financière & Moteur de Calcul
- **Isolation du Domaine Financier (Règle Cardinale) :**
    - Tous les calculs vivent EXCLUSIVEMENT dans le paquet `saucante74/finlr-engine` via `CalculatorEngineInterface`.
    - Aucune formule financière ne doit être réimplémentée en PHP ou TypeScript dans l'application.
- **Rigueur Numérique :**
    - Les calculs de montants financiers sensibles ne doivent jamais subir d'erreurs de précision liées aux flottants.
    - Les taux, frais (5 catégories) et règles fiscales doivent être configurés via `config/financial.php` et encapsulés proprement.

---

## 3. Architecture Backend (Laravel Monolithe Modulaire)
- **Principes SOLID & KISS :**
    - **Single Responsibility :** Les contrôleurs sont Single-Action (`__invoke()`). La logique métier réside dans des `Actions` (`handle()`).
    - **Open/Closed :** Proscrire les `match()` dispersés sur un Enum. La logique spécifique à un cas d'enum doit résidere au sein de l'Enum lui-même (ex: `$enum->grants()`).
- **Validation & Typage Strict (PHP 8.3) :**
    - Interdiction des tableaux associatifs opaques (`array`, `array<string, mixed>`) pour passer des structures de données à un Service/Action.
    - Utilisation obligatoire de **DTOs immuables** (`readonly class`) dans `DTOs/`.
    - Utilisation des helpers typés natifs (`$request->string()`, `$request->float()`, etc.) sur les `FormRequest`.
    - Interdiction des annotations PHPDoc de contournement quand un type PHP 8.3 natif existe.

---

## 4. Architecture Frontend (React + TypeScript + Inertia)
- **Feature-Based Architecture :**
    - Les composants métier vivent dans `resources/js/features/{feature_name}/`.
    - Les pages (`resources/js/pages/`) sont de simples réceptacles d'assemblage sans logique métier.
    - Seuls les composants agnostiques vivent dans `resources/js/components/ui/`.
- **Typage Strict TypeScript :**
    - **Interdiction absolue du type `any`**, de `object` ou de `Record<string, any>`.
    - Les interfaces reflétant les DTOs backend vivent exclusivement dans `resources/js/features/{feature}/types/index.ts`.
- **i18n & Dark Mode :**
    - Aucun texte en dur dans le JSX (`react-i18next`).
    - Utilisation des tokens sémantiques Tailwind v4 (`bg-background`, `text-muted-foreground`).

---

## 5. Couverture de Tests (PHPUnit & Vitest)
- Toute nouvelle Action ou fonctionnalité doit être couverte par un test PHPUnit (`./vendor/bin/sail test`).
- Test des cas limites (edge cases) : montants nuls, taux négatifs, bornes d'enveloppes fiscales, entrées malformées.
