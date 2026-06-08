# Deployment

## Recommended Source Of Truth

GitHub should be the source of truth for code. SiteGround should be the deployment target.

## Files

Deploy these tracked folders to production under `wp-content`:

- `mu-plugins/`
- `components/`

Do not deploy repository metadata like `.git/` to the public web root unless intentionally using SiteGround's Git tooling.

## SiteGround Options

SiteGround tools that can help:

- Site Tools > Devs > Git can create a deployable repository on SiteGround.
- Site Tools > Site > File Manager can upload files manually.
- FTP/SFTP can sync tracked files manually or from an automation.
- Staging is useful for testing before live deploy, but it is not the source of truth.

## Content Patches

Content patches in `migrations/` are database changes. Run them deliberately, verify output, then remove any temporary web-accessible runner script if one was used.
