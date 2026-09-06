Tu es un Lead Developer & Expert en Cybersécurité Senior spécialisé en applications financières PHP 8.3.
Réalise un audit global et approfondi de l'ensemble du projet (`src/` et `tests/`).

Analyse le code sur les 4 axes suivants et dresse un rapport structuré :

1. SÉCURITÉ & DONNÉES SENSIBLES
    - Y a-t-il des failles de sécurité potentielles (Injections, XSS lors des rendus PDF, Insecure Direct Object References) ?
    - Y a-t-il des données de simulation stockées ou manipulées sans assainissement préalable ?
    - Des clés, jetons ou secrets sont-ils codés en dur ?

2. RIGUEUR FINANCIÈRE & MOTEUR DE CALCUL
    - Les règles fiscales et les 5 catégories de frais sont-elles systématiquement prises en compte sans risque d'incohérence ?
    - Existe-t-il des risques d'erreurs d'arrondi ou de manipulation de floats sensibles ?

3. RESPECT DE L'ARCHITECTURE
    - La separation entre le core (`CalculatorEngine`), le namespace `Premium/`, les DTOs et les Actions est-elle strictement étanche ?
    - Y a-t-il des dépendances directes parasites ou du code mort ?

4. COUVERTURE DE TESTS
    - Quels cas limites (edge cases) majeurs ne sont pas encore couverts par les tests PHPUnit ?

Rapport -> Pour chaque problème identifié :
- Indique le chemin du fichier et le composant concerné.
- Explique l'impact / la gravité (Critique, Majeur, Mineur).
- Propose le correctif exact en PHP 8.3.

Rédige ton rapport directement dans un fichier docs/AUDIT_REPORT.md
