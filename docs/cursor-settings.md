# Cursor Settings On Another Computer

## Editor Preferences

Use Cursor/VS Code Settings Sync for normal editor preferences:

- Settings
- Keybindings
- Extensions
- Themes
- UI preferences

If Settings Sync is not enough, compare/copy selected values from:

```text
~/Library/Application Support/Cursor/User/settings.json
```

Do not blindly copy all of `~/Library/Application Support/Cursor/User/`; it contains machine-local state, history, caches, and extension storage.

## Cursor Account And Models

Sign into the same Cursor account on the other computer. Account-level Cursor features, plan access, and model availability should follow the account.

## MCP/API Connections

MCP connections and API-backed integrations may require separate setup on the other computer.

Expected steps:

1. Install any required CLIs for MCP servers.
2. Re-authenticate integrations such as Slack, Atlassian, GitHub, or browser tooling when Cursor prompts.
3. Do not copy raw tokens unless you know exactly where they are stored and how they are secured.
4. Prefer each integration's normal login/auth flow on the new machine.

This machine has project-scoped MCP descriptors under `.cursor/projects/.../mcps/`, but those are generated local Cursor state, not something this repo should commit.

## AI Foundation Prompts

Clone Nathan's prompt repo separately on the other computer, ideally to the same path used by the user rule:

```bash
mkdir -p /Users/nathanhiemstra/_mine
# preferred path: /Users/nathanhiemstra/_mine/ai-foundation
git clone https://github.com/nathanhiemstra/ai-foundation.git /Users/nathanhiemstra/_mine/ai-foundation
```

If the username/path differs on the other laptop, update the Cursor user rule to point to the actual prompt path.
