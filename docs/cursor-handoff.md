# Cursor Handoff

Use this when opening the Quotes project in Cursor on another computer.

## Start Here

1. Clone the repo:

```bash
git clone https://github.com/nathanhiemstra/quotes.git
```

2. Open the cloned repo in Cursor.
3. Read these files first:
   - `README.md`
   - `docs/local-setup.md`
   - `docs/deployment.md`
   - `.cursor/rules/quotes-project.mdc`
4. If available on the machine, also read Nathan's AI foundation prompts:
   - `/Users/nathanhiemstra/_mine/ai-foundation/prompts/base/communication.md`
   - `/Users/nathanhiemstra/_mine/ai-foundation/prompts/base/workflow.md`
   - `/Users/nathanhiemstra/_mine/ai-foundation/prompts/roles/senior-front-end-developer.md`
   - `/Users/nathanhiemstra/_mine/ai-foundation/prompts/domains/frontend.md`
   - `/Users/nathanhiemstra/_mine/ai-foundation/prompts/domains/wordpress.md`
   - `/Users/nathanhiemstra/_mine/ai-foundation/prompts/domains/accessibility.md`

## Current Architecture

This repo is meant to be checked out as, or copied into, WordPress `wp-content`.

Tracked project code:

- `mu-plugins/` - custom quote site behavior.
- `components/` - component docs/examples.
- `migrations/` - repeatable content/database patches.
- `docs/` - setup/deployment notes.

Not tracked:

- WordPress core.
- `wp-config.php`.
- `uploads/`.
- caches/backups/logs.
- third-party plugins/themes installed per environment.

## What Was Recently Done

The live SiteGround site and Local site were updated with:

- Semantic quote card structure: `article`, `time`, `blockquote`, `cite`.
- Clear archive headings like `Quotes from 2026` and `Quotes by Ariana M.`.
- Mobile long-quote sizing.
- Previous/random/next quote/archive navigation.
- Left/right arrow-key navigation.
- Several quote content fixes and additions.
- Production mojibake cleanup.

The repeatable content portion is stored in:

- `migrations/2026-06-08-content-fixes.php`

## Long-Term Suggestions

Prioritize these before building social-network features:

1. Add a real homepage with clear paths: browse by year, browse by person, random quote, search.
2. Add an author index with counts and filtering.
3. Add full-site quote search.
4. Add copy/share quote actions.
5. Add Open Graph/social preview images for individual quote pages.
6. Curate subject/collection pages later, for example `Best of camp`, `Kids`, `Overheard`, `Family`, `Absurd`.
7. If submissions are added, start with moderated quote submission, not accounts/follows/feeds.

## Working Rules

- Keep changes scoped and simple.
- Prefer server-rendered WordPress/PHP over a heavy JavaScript app.
- Put one-off content mutations in `migrations/`.
- After editing PHP, run `php -l` on changed files.
- Before deploy, review `git diff` and confirm no secrets/generated files are staged.
