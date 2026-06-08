# Quotes WordPress Content

This repository is the portable `wp-content` layer for the Quotes WordPress site.

It intentionally tracks only project-owned code and repeatable migration scripts. It does not track WordPress core, uploads, caches, backups, `wp-config.php`, or paid third-party plugin code.

## Layout

- `mu-plugins/` - custom WordPress behavior for quote taxonomies, rendering, admin fields, and local docs.
- `components/` - project-owned component examples used by the local docs page.
- `migrations/` - repeatable one-off content/data patches.
- `docs/` - local setup and deployment notes.

## Remote

GitHub remote:

```bash
https://github.com/nathanhiemstra/quotes.git
```

## Local Usage

For a new Local WP install, clone this repository as `wp-content` or clone it elsewhere and copy/symlink its contents into `app/public/wp-content`.

See `docs/local-setup.md`.

## Deployment

Deploy tracked files to SiteGround's `wp-content` directory. Content patches in `migrations/` should be run deliberately and only once unless documented as idempotent.

See `docs/deployment.md`.
