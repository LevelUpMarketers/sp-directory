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
- The demo cron event `sd_demo_cron_event` exists only to showcase the Cron Jobs tab—remove it for production or client builds.
- The Cron Jobs tab auto-detects cron hooks prefixed with `sd_`; continue using this prefix for future scheduled tasks.
- Whenever you add a cron hook, document a clear, human-friendly description so it appears in the Cron Jobs tab tooltip.
- Align all backend feedback/status areas with the current inline spinner + message pattern that sits beside action buttons to prevent layout shifts.
- Match grouped admin action buttons (like cron controls) to the shared min-width styling so side-by-side buttons render with consistent dimensions regardless of label length.
- The Communications page currently ships with sample accordion data—remove these demos and supply real templates before shipping to clients or WordPress.org.
- Reuse the Communications accordion pattern whenever you need expandable admin sections; mirror the current list-table layout, keep tooltip-ready descriptions, and retain the rotating indicator arrow.
- Every admin tab should include the standardized title-and-description block (see `render_tab_intro()`), placed after `.sd-top-message` and before tab-specific content.
