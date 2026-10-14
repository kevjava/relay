# Relay Agent Guidelines

Relay is a file-based PHP CMS. It targets PHP 8.1 or newer and stores content in Markdown and configuration in JSON; it does not use a database.

Use these documents as the source of truth instead of duplicating their details here:

- [Project specification](spec.md) for implemented architecture and behavior
- [README](README.md) for setup, deployment, and usage
- [Security policy](SECURITY.md) for security and production hardening
- [Changelog](CHANGELOG.md) for release history

## Architecture

- Entry points are `index.php`, `admin.php`, `admin-tools.php`, and `error-404.php`.
- Core behavior lives in procedural modules under `lib/`. Public functions use a library prefix such as `content_`, `menu_`, `auth_`, `theme_`, or `relay_`.
- Each library must declare its own dependencies with `require_once`; do not rely on incidental bootstrap or Composer loading. Keep `tests/DependencyTest.php` passing.
- Use `relay_json_load()` and `relay_json_save()` for JSON persistence.
- Use the shared session helper in `lib/session.php`; retain compatibility wrappers such as `auth_init_session()` where public behavior depends on them.
- Use `url_base()` for generated application URLs and redirects so root and subdirectory deployments both work.
- Themes are trusted PHP code. Markdown content is trusted filesystem-managed content and may contain raw HTML.

## Code Conventions

- Match the existing procedural PHP style and add scalar, union, and return types where the surrounding API uses them.
- Preserve public function names, return contracts, JSON schemas, frontmatter fields, and URL behavior unless the task explicitly changes them.
- Define library constants near the top of their owning module.
- Do not add closing `?>` tags to PHP-only library files.
- Escape user-facing text with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` unless the value is an intentional trusted HTML fragment.
- Keep changes focused. Do not reformat unrelated code or edit vendored dependencies.
- Update `spec.md` when implemented architecture or public behavior changes. Update release documentation only when the task requires it.

## Security Invariants

- Treat path validation, authentication, sessions, CSRF, output escaping, and filesystem writes as security-sensitive code.
- Use `relay_path_is_valid_identifier()` for the shared identifier policy and `relay_path_resolve_within()` for canonical containment checks.
- Keep content- and theme-specific path wrappers separate: their normalization, defaults, nesting, precedence, and fallback policies intentionally differ.
- Canonical path checks must reject missing paths, traversal, symlink escapes, and sibling-prefix collisions.
- Require CSRF validation for every state-changing web request.
- Never weaken session cookie settings, login throttling, timeout handling, or password hashing without an explicit requirement and regression tests.
- Do not log, print, or commit credentials, password hashes, CSRF tokens, or session identifiers.

## Testing

Install PHP dependencies with `composer install` and JavaScript test dependencies with `npm install` when needed.

Run the narrowest relevant test first:

```bash
vendor/bin/phpunit tests/ContentTest.php
vendor/bin/phpunit --filter testName
```

Before completing a PHP change, run the full suite:

```bash
composer test
```

For coverage-sensitive changes:

```bash
composer test:coverage
```

For admin or menu-editor browser behavior:

```bash
npm run test:browser
```

- Add focused regression tests for bug fixes and security boundary changes.
- Use `tests/Support/FilesystemTestCase.php` for temporary filesystem fixtures and cleanup.
- Preserve direct-library loading coverage when adding or changing module dependencies.
- Do not commit generated output from `coverage-html/`, `test-results/`, PHPUnit caches, or temporary coverage reports.

## Change Discipline

- Inspect the current worktree before editing and preserve unrelated user changes.
- Prefer existing helpers and local patterns over new abstractions.
- Add a shared abstraction only when it removes meaningful duplication or centralizes a security invariant.
- Verify both successful behavior and rejection/fallback paths.
- Keep configuration and content files portable; stored menu URLs must not include the deployment base path.
