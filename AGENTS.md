# AGENTS

Welcome to the SuperDirectory repository.

## Development Workflow

1. Run syntax checks on all PHP files:
   ```bash
   find . -name "*.php" -not -path "*/vendor/*" -print0 | xargs -0 -n1 php -l
   ```
2. If adding JavaScript or CSS, ensure files are linted if linters are available.
3. Update `DEV_DIARY.md` with a new numbered, timestamped entry after every change.
4. Update `README.md` when plugin usage changes.

## Notes
- Keep code modular and translation ready.
- Treat every new or modified string as translatable; wrap user-facing text in the appropriate internationalization helpers and
  provide translator comments where context is needed.
- Custom database table prefix is `sd_`.
- Remove `includes/update-server/` before submitting to WP.org.
- Align all backend feedback/status areas with the current inline spinner + message pattern that sits beside action buttons to prevent layout shifts.
- Match grouped admin action buttons to the shared min-width styling so side-by-side buttons render with consistent dimensions regardless of label length.
- Every admin tab should include the standardized title-and-description block (see `render_tab_intro()`), placed after `.sd-top-message` and before tab-specific content.
