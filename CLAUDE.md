# Directives Projet Finlr — Laravel + Sail + React + Tailwind v4

> Ce fichier est la source de vérité des conventions du projet. Il est versionné
> avec le code (ne jamais le remettre dans `.gitignore`). Toute évolution
> d'architecture doit être répercutée ici dans le même commit.

INTERDICTION ABSOLUE DE COMMITER : n'exécute JAMAIS `git commit`, `git add`, `git push`, `git checkout`, `git switch`, `git branch`, `git stash`, `git reset` ni `git restore`. Le versionnement est géré manuellement par moi seul. Tu peux lire l'état du dépôt (`git status`, `git diff`, `git log`) mais tu ne modifies jamais l'index ni l'historique : laisse simplement tes modifications dans le working tree.
---

## Langue

- **Code en anglais :** noms de classes, variables, méthodes, fichiers, dossiers,
  commentaires, clés i18n et messages de commit.
- **Documentation en français :** fichiers `.md`, prompts, spécifications.
- Les *valeurs* des traductions suivent leur langue (`lang/fr.json` en français) ;
  seules les *clés* restent en anglais.

---

## Environnement & Stack

- Laravel 13 exécuté impérativement via Laravel Sail (`./vendor/bin/sail`)
- PHP 8.3 (contrainte `composer.json` et CI)
- Frontend : Inertia.js v2 + React 18 + TypeScript
- Styles : Tailwind CSS v4 + shadcn/ui
- Base de données : PostgreSQL 15 (service `pgsql` de `compose.yaml`)
- Tests PHP : **PHPUnit** (Pest n'est pas installé sur ce projet)
- Tests front : Vitest + Testing Library

## Règles d'Exécution Strictes

- Toujours préfixer les commandes PHP et NPM par Sail :
  `./vendor/bin/sail php ...`, `./vendor/bin/sail npm ...`, `./vendor/bin/sail test`
- Ne JAMAIS inspecter ou modifier le contenu de `node_modules/` ou `vendor/`.
  Seule exception : consulter (en lecture) l'API publique de `saucante74/finlr-engine`.
- Par défaut, préférer des modifications minimales et ciblées plutôt que des
  refactorisations globales. Une refactorisation large n'est acceptable que si
  elle est explicitement demandée, et doit alors être découpée en commits atomiques.
- Ne pas toucher aux fichiers de configuration (`vite.config.ts`, `package.json`,
  `composer.json`, `tsconfig.json`, `phpstan.neon`) sans demande explicite.
- Tailwind v4 : la configuration réelle vit dans `resources/css/app.css`
  (bloc `@theme`). Il n'existe pas de `tailwind.config.js`.

---

##  Architecture & Normes de Code

**Vocabulaire :** le backend est un **monolithe modulaire** (vertical slices) sous
`app/Modules/` ; le frontend est **feature-based** sous `resources/js/features/`.

### Backend (Laravel / modulaire)

- **Découpage par module métier :** `app/Modules/[ModuleName]/`
  (`Auth`, `User`, `Calculator`, `Subscriptions`, `Shared`).
  Chaque module contient sa propre pile : `Actions/`, `Controllers/`, `DTOs/`,
  `Requests/`, `Enums/`, `Contracts/`, `Services/`, `Models/`.
- **Principe du `/Shared` :**
    - Seules les classes purement transversales et **sans logique métier** y sont
      autorisées (helpers génériques, middlewares globaux, enums de base).
    - La gestion de profil, la suppression de compte et l'authentification vivent
      dans leurs modules respectifs (`User`, `Auth`).
- **Principes S.O.L.I.D :**
    - **Controllers :** toujours single-action (invokables avec `__invoke()`).
    - **Validation :** toujours via des classes `FormRequest` sous `Requests/`.
    - **Métier :** toute la logique réside dans des classes `Action` sous
      `Actions/`, exposant une méthode `handle()`.
    - **Open/Closed :** proscrire les `match()` sur un enum en dehors de cet enum.
      Une règle qui dépend d'un cas d'enum est une méthode de l'enum lui-même
      (ex. `Plan::grants(Permission $permission): bool`), jamais un `match()`
      dispersé dans un ServiceProvider ou un contrôleur.

- **Moteur financier — règle cardinale :**
    - Tous les calculs financiers vivent **exclusivement** dans la dépendance
      privée `saucante74/finlr-engine`.
    - L'application ne dépend JAMAIS du paquet directement : elle passe par
      `CalculatorEngineInterface` et son adapter, dans `app/Modules/Calculator/`.
    - **Ne jamais dupliquer ni réimplémenter une formule financière** dans
      l'application, ni en PHP, ni en TypeScript. Une formule dupliquée est une
      divergence garantie à la première évolution fiscale.
    - Il n'existe pas de dossier `app/Domain/` : le domaine financier est le paquet.

- **PHP 8 & interdiction des PHPDoc « bricolages » :**
    - Utiliser exclusivement les types natifs stricts PHP 8. Proscrire les PHPDocs
      verbeux ou de contournement (`/** @var array... */`).
    - Pour extraire les données d'un `FormRequest` vers un DTO, utiliser les
      helpers typés natifs (`$request->string()`, `$request->float()`,
      `$request->integer()`, `$request->boolean()`) ou le cast explicite PHP.

- **Typage strict & DTOs suffixés `*Data` (interdiction des tableaux opaques) :**
    - Ne JAMAIS passer de tableau associatif générique (`array`,
      `array<string, mixed>`) à une Action, un Service ou un constructeur.
    - Créer systématiquement un **DTO immuable** (`readonly class`) dans le dossier
      `DTOs/` du module concerné pour encapsuler les paramètres d'entrée/sortie.
    - Cette règle vaut aussi pour la lecture de configuration : un objet de config
      est un `readonly class` typé dans `DTOs/`, pas une classe `Support/` qui
      trimballe des `array`.

- **Tests :** chaque nouvelle Action ou fonctionnalité doit être couverte par un
  test PHPUnit (`./vendor/bin/sail test`). La suite doit être verte avant tout commit.

### Frontend (React + TypeScript + Inertia)

- **Arborescence `/resources/js` :**
    - Conserver `/resources/js` (ne JAMAIS utiliser `/resources/ts`).
    - N'utiliser QUE le dossier minuscule `/components` (jamais `/Components`).
    - `/resources/js/components/ui/` : composants UI agnostiques uniquement
      (Button, Card, Input). Aucun code métier ici.
    - `/resources/js/features/{feature_name}/` : composants, hooks, types et
      sous-éléments propres à un domaine (`calculator`, `auth`, `user`).
    - `/resources/js/pages/` : vues Inertia minimalistes qui assemblent les
      composants issus des `features`. Aucune logique métier dans une page.
- **Typage TypeScript strict :**
    - **Interdiction du type `any`.** Utiliser des `interface` ou `type` dédiés.
    - **Miroir des DTOs :** les types reflétant les DTOs Laravel vivent dans
      `resources/js/features/{feature}/types/index.ts` — et nulle part ailleurs.
      Ne jamais exporter un type de données depuis un fichier de composant.
    - **Pas d'objets génériques :** proscrire `Record<string, any>` et `object`
      au profit d'interfaces explicites.
    - Les props de page sont typées via le générique de `usePage<PageProps>()`
      et les types partagés de `resources/js/types/index.ts`.

---

## Sécurité

- Toute route publique en `POST` doit porter un `throttle` explicite
  (inscription, mot de passe oublié, réinitialisation, tout endpoint de calcul).
- Les props Inertia partagées n'exposent **jamais** un modèle Eloquent complet :
  toujours un tableau explicite des champs strictement nécessaires. Rappel : ces
  props sont sérialisées dans le HTML de **toutes** les pages, y compris publiques.
- **Toute capacité payante est vérifiée côté serveur** (Gate ou middleware) dans
  le contrôleur ou l'Action. Une vérification côté React relève de l'affichage,
  jamais de l'autorisation.
- Le middleware `verified` s'applique à toute route manipulant des données
  utilisateur, pas seulement au dashboard.
- Aucun secret, aucune clé, aucun token en dur dans le code ou dans un `.md`.
  Toute nouvelle variable ajoutée à `.env` doit l'être aussi dans `.env.example`
  (valeur factice ou vide).

---

## Exigences Techniques Non-Négociables

1. **Internationalisation (i18n) complète :**
    - AUCUN texte en dur dans le code React.
    - `react-i18next` pour les traductions (FR, EN, IT).
    - Tous les labels (titres, champs, KPI, légendes du graphique, messages
      d'erreur) centralisés dans `lang/fr.json`, `lang/en.json`, `lang/it.json`.
    - Les trois fichiers doivent avoir des jeux de clés strictement identiques
      (vérifié par `resources/js/test/i18n-keys.test.ts`).
    - Les messages serveur (validation, auth) doivent exister dans les trois
      langues sous `lang/{fr,en,it}/`.

2. **Pas de valeurs codées en dur :**
    - `config/financial.php` centralise les taux de taxe suggérés (PEA, CTO, AV)
      et les paramètres par défaut du calculateur, injectés dans les vues Inertia.
    - Cela vaut aussi côté front : devise, locale de repli, couleurs de séries du
      graphique et paliers d'axes ne sont pas des littéraux dispersés dans les
      composants.

3. **Dark mode :**
    - Thème piloté par variables CSS (`:root` / `.dark` dans `resources/css/app.css`) ;
      privilégier les tokens sémantiques (`bg-background`, `text-muted-foreground`)
      aux variantes `dark:` ponctuelles.
    - Bouton bascule soleil/lune gérant la classe `dark` sur `<html>` via un hook
      React persistant (`localStorage`, clé `finlr_theme`), avec script anti-FOUC
      dans `app.blade.php`.

---

## Journal de Décisions Produit

> Décisions d'architecture ou de produit qui dérogent délibérément à une
> règle générale de ce fichier, avec leur justification — pour qu'une
> future session (humaine ou IA) ne "corrige" pas un choix assumé en
> pensant réparer un oubli. Ajouter une entrée datée en bas de la liste
> plutôt que de réécrire les entrées existantes.

- **2026-09 — Le calculateur freemium (`/resources/js/features/
  freemium-calculator/`) reste 100% client, jamais branché sur
  `saucante74/finlr-engine` :**
    - Ce n'est **pas** une exception oubliée à la règle cardinale « tous les
      calculs financiers vivent exclusivement dans `saucante74/finlr-engine` »
      (voir « Architecture & Normes de Code » ci-dessus) — c'est une décision
      produit délibérée. La valeur de cette page pour l'acquisition de trafic
      est le retour instantané pendant que l'utilisateur tape (`useMemo` +
      `computeCompound()` en synchrone) ; un appel serveur à chaque frappe
      casserait cette expérience, et un debounce introduirait la latence que
      la page existe justement pour éviter.
    - La formule de croissance composée est isolée dans la fonction pure
      `computeCompound()` (`resources/js/features/freemium-calculator/lib/
      compound.ts`), testée unitairement (`compound.test.ts`, valeurs de
      référence dérivées de la formule de rente à forme close). Son docblock
      documente explicitement ce qui est simplifié (fiscalité réelle réduite
      à un taux plat, 5 typologies de frais du moteur premium réduites à
      deux pourcentages) et la date de dernière vérification de cohérence
      avec les taux courants.
    - **Garde-fou de dérive :** si les taux fiscaux (PEA/CTO/AV) ou
      l'algorithme de croissance du moteur premium `saucante74/finlr-engine`
      changent significativement, vérifier manuellement si ce calculateur
      freemium reste une approximation raisonnable — rien ne le fait
      automatiquement, ni test, ni CI, puisqu'il n'existe aucun lien
      technique entre les deux. Le point de départ de cette vérification est
      le docblock de `computeCompound()` et le commentaire en tête de
      `constants.ts`.
    - Le risque de confusion utilisateur (prendre ce chiffre pour un
      résultat fiscalement exact) est traité côté transparence, pas côté
      précision : un bandeau visible en permanence (`FreemiumDisclaimer.tsx`,
      affiché sur la page, pas seulement une info-bulle survolée) rappelle
      qu'il s'agit d'une estimation et renvoie vers `/simulators` pour un
      résultat précis.

---

## Qualité de Code & CI

- **Formatage (Laravel Pint) :** `composer format` (alias de `vendor/bin/pint`).
- **Analyse statique (Larastan/PHPStan, niveau 5) :** `composer analyse`.
  Aucun fichier de `app/` ne doit être ajouté à `excludePaths` : une exclusion
  masque les erreurs au lieu de les corriger.
- **Le hook pre-commit réel n'est PAS `.git/hooks/pre-commit` :**
  `core.hooksPath` pointe vers `.githooks/` (dossier versionné) — c'est
  `.githooks/pre-commit` qui s'exécute réellement à chaque commit. Un fichier
  `.git/hooks/pre-commit` peut exister localement sans jamais s'exécuter ;
  vérifie toujours `git config --get core.hooksPath` avant de faire confiance
  à un hook trouvé sous `.git/hooks/`.
- **`.githooks/pre-commit` doit rester strictement identique à `composer
  analyse` :** il exécute PHPStan sur les fichiers PHP stagés (via Sail,
  cache vidé au préalable) **sans flag `--level`**, pour hériter du niveau
  défini dans `phpstan.neon` (actuellement 5) — exactement comme `composer
  analyse`. Un incident précédent (2026-09) a montré qu'un `--level=8`
  auparavant codé en dur dans ce hook faisait diverger silencieusement les
  deux commandes : `composer analyse` annonçait 0 erreur pendant que le hook
  en bloquait plusieurs (accès nullable, types de tableaux génériques
  manquants — tout ce que les niveaux 6 à 8 ajoutent). Si `phpstan.neon`
  doit un jour monter de niveau, cela doit rester la **seule** source de
  vérité — ne jamais réintroduire un `--level` propre au hook. **Avant de
  déclarer "PHPStan au vert" dans un rapport de tâche, exécute aussi
  `.githooks/pre-commit` sur les fichiers modifiés (pas seulement
  `composer analyse`)** : le hook cible spécifiquement les fichiers stagés
  via Sail (PHP 8.3, conforme au projet), ce qui peut différer d'une
  exécution `composer analyse` lancée hors Sail.
- **Typecheck frontend :** `./vendor/bin/sail npm run typecheck`.
- **Tests :** `./vendor/bin/sail test` (PHPUnit) et
  `./vendor/bin/sail npm run test` (Vitest).
- **Intégration continue :** `.github/workflows/ci.yml` s'exécute sur chaque
  `push`/`pull_request` vers `develop` et `master` : `pint --test`,
  `phpstan analyse`, `tsc --noEmit`, `php artisan test`, `npm run test`.
  Toute PR doit passer au vert.
