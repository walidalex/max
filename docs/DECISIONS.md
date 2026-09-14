# Decisions

1. No PHP framework yet; the foundation stays small and explicit.
2. No Vite or bundler; npm packages are copied from their published `dist` folders by `npm run assets:build`.
3. Tabler's official RTL stylesheet is the UI base. Bootstrap is not installed separately because Tabler ships it.
4. Composer has no runtime package beyond the PHP and MySQLi platform requirements.
5. The `Foundation` module demonstrates the intended dependency flow and is not an ERP business module.
6. Authentication uses database-backed users, password hashing, active-state checks, and session idle validation.
7. State-changing browser requests use a session-bound CSRF token.
8. Authorization is permission-based. Role names are not checked in controllers or business services.
9. Permission relationships are queried on every authorization check; they are not cached in Session.
10. The protected system role bypass is implemented only in `AuthorizationService`.
11. Users are activated or deactivated and never physically deleted.
12. The application supports exactly one company profile, enforced as record `id = 1` in the database and service layer.
13. Company logos remain outside the public web root and require `company_profile.view` to stream.
14. Logo filenames are random and extensions follow detected MIME (`png`, `jpg`, or `webp`); files are not converted.
