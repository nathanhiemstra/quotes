# Local Setup

## Goal

Run the Quotes site from any laptop using Local WP plus this repository as the portable `wp-content` layer.

## Fresh Local Site

1. Create a new Local WP site named `quotes`.
2. Start the site.
3. Open the site folder.
4. Replace or populate `app/public/wp-content` with this repository.

One option:

```bash
cd "~/Local Sites/quotes/app/public"
mv wp-content wp-content.default
git clone https://github.com/nathanhiemstra/quotes.git wp-content
```

If you already have a working Local site, copy this repo's tracked folders into `app/public/wp-content` instead:

```bash
cp -R mu-plugins components "~/Local Sites/quotes/app/public/wp-content/"
```

## Plugins

Install these in Local separately when needed:

- Advanced Custom Fields Pro, for the quote admin field UI.
- Admin Columns, optional, for admin list enhancements.

Do not commit paid plugin source unless you intentionally decide this private repo should store it.

## Content

The custom code expects normal WordPress posts plus the `quote_author` taxonomy. Historical import data and importer scripts live outside this repository in the larger local project export area.

After importing content, run any needed migration scripts from `migrations/` through WP-CLI or a temporary authenticated deployment route.
