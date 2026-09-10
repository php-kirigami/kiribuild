# CLAUDE.md

Project context and working conventions for **KiriBuild**. Committed to the repo
so it travels with the code across machines and contributors.

---

## What this is

**KiriBuild** is the reusable GitHub Action for [Kirigami](https://github.com/php-kirigami/kirigami)
projects. It makes sure the runner has Node 24+ and the `kiri` CLI available
(local dependency first, global install as fallback), then runs `kiri export`.
That's the whole job — **checkout and committing/deploying the export are the
caller's responsibility** (this changed in v2; v1 also did checkout + commit).

- **Repo:** `php-kirigami/kiribuild` (`origin`, branch `main`). GitHub org
  `php-kirigami`.
- **Type:** a **composite** GitHub Action — the whole thing is `action.yml` at
  the repo root, no build step, no `dist/`, no bundled JS.
- **Consumed as:** `uses: php-kirigami/kiribuild@v2` from a project's
  `.github/workflows/*.yml`.
- **Sole author / maintainer:** Maxime Larrivée-Roy.
- Sits next to the other Kirigami repos as `../kiribuild/` (see the main repo's
  `CLAUDE.md` for the full sibling list). The `kiri` CLI it drives is
  `@kirigami/kirigami` in `../kirigami/packages/kirigami/`.

---

## Repo layout

```
.
├── action.yml                 # the entire action (composite, root action)
├── README.md                  # user-facing docs (usage, inputs, Pages example)
├── LICENSE                    # MIT
├── .gitignore                 # ignores the fixture's node_modules / dist / caches
├── test/fixtures/site/        # tiny Kirigami project for the cli-resolution test
├── .github/workflows/test.yml # CI: `features` (template-demo) + `cli-resolution` (matrix)
├── .actrc                     # config for local `act` runs (currently empty)
├── tag.txt / push.bat         # personal release cheat-sheets (not part of the action)
```

There is **no** `CONTRIBUTING.md` yet, though `README.md` links to one.

---

## How the action works (`action.yml`)

Composite steps, in order. The caller has **already checked out** the repo.

1. **Check for Node 24+** — reads `process.versions.node`; sets step output
   `satisfied=true/false`.
2. **Setup Node** — `actions/setup-node@v7`, `node-version` input; runs **only
   if `satisfied != 'true'`**. No `cache:` (there may be no lockfile, and
   caching is the caller's call).
3. **Install project dependencies** — only if `package.json` exists
   (`hashFiles('package.json') != ''`) **and** `node_modules/` is absent (so a
   caller that already installed isn't re-run). `npm ci` when a
   `package-lock.json` / `npm-shrinkwrap.json` is committed (it never rewrites
   the lockfile, so the caller's `git add -A` commit-back step has nothing to
   pick up); `npm install` otherwise.
4. **Ensure the Kirigami CLI is available** — if `./node_modules/.bin/kiri` is
   missing, `npm install -g @kirigami/kirigami@<kirigami-version>` and append
   `$(npm config get prefix)/bin` to `$GITHUB_PATH`.
5. **Export** — picks `./node_modules/.bin/kiri` if present else `$(which kiri)`,
   then runs `node --experimental-wasm-jspi "$KIRI_BIN" export`. The JSPI flag
   is required by `@kirigami/php-wasm`.

### Inputs

| Name | Default | Notes |
|---|---|---|
| `node-version` | `24` | Only used when the runner doesn't already have Node 24+. |
| `kirigami-version` | `latest` | Only used for the global fallback install. |

Removed in v2: `lfs` and `commit-message` (checkout and commit left to the caller).

### Outputs

None. Checkout, commit, artifact upload and Pages deploy are all the caller's
workflow (see `README.md` for the full Pages example with an explicit checkout
and commit step).

---

## Versioning & releasing

Two kinds of tags:

- **Precise tags** (`v2.0.0`, …) — annotated, **never moved** once pushed.
- **Floating major tag** (`v2`) — force-moved to point at the latest precise
  tag, so `uses: …@v2` tracks the newest v2.x.

v2 is a breaking change (checkout + commit removed, `lfs` / `commit-message`
inputs gone), so it's a new major. The `v1` tag and the `v1.0.x` tags stay
untouched for callers that haven't migrated.

Release steps (from `tag.txt` / `push.bat`):

```bash
git add . && git commit -m "…" && git push origin main
git tag -a v2.0.1 -m "…"
git push origin v2.0.1
git tag -f v2 v2.0.1
git push origin v2 --force
```

The `-f` / `--force` is expected — but only ever on the floating major tag.

---

## Local testing

`.github/workflows/test.yml` has two jobs, both on every push / PR:

The workflow has a top-level `env: KIRI_GOOD` — the newest `@kirigami/kirigami`
known to install + export cleanly (currently `1.3.2`). `latest` has a history of
shipping broken, so the gating jobs pin `KIRI_GOOD` (the workflow rewrites each
staged `package.json`'s `@kirigami/kirigami` to `KIRI_GOOD` — and drops any
committed lockfile — before the action runs). Bump that one line once
`latest-canary` has been green for a while — or
drop the pinning if upstream releases stabilise.

- **`cli-resolution`** — a matrix over `test/fixtures/site`, a tiny project
  depending only on `@kirigami/kirigami`. Scenarios: `local-cli`, `global-cli`,
  `preinstalled` (asserts `npm install` is skipped, via a sentinel file in
  `node_modules/`), `lockfile` (writes a `package-lock.json` via
  `npm install --package-lock-only` and no `node_modules/`, asserts the action
  takes the `npm ci` branch and still exports), `has-node-24` (pins Node
  `24.0.0` first, asserts the action's
  setup-node is skipped by checking `node -v` is still `v24.0.0`),
  `pinned-version` (installs `kirigami-version: 1.1.2` — a non-latest value — and
  asserts that exact version lands and still exports), `latest-canary`
  (`continue-on-error`, non-blocking — runs `latest`; green ⇒ bump `KIRI_GOOD`).
  Every full scenario also asserts the core feature surface: layouts, `@stats`
  data file, `<markdown>`, a custom `<uppercase>` tag, a `post_render` hook, the
  image autogenerator (`dist/images/*.webp`), sitemap.
- **`templates`** — a matrix over the official templates (pinned to `KIRI_GOOD`),
  staged at the workspace root and run through the action. Both `template-default`
  (pages + `@kirigami/canva` Sass output + esbuild output + sitemap) and
  `template-demo` (all `features/*` pages, image autogenerator, `<swatches>` tag,
  highlight plugin, YAML/JSON data loops) are **required**.

Published-version history worth knowing: `@kirigami/kirigami` **1.2.0** (broken
bin), **1.3.0** (invalid bundled `kirigami.schema.json`), and briefly **1.3.2**
(dep `@kirigami/php-prepros@1.6.1` lagged the CDN) each blocked every
`kiri export` / install. **1.3.1** worked for the fixture + `template-default`
but not `template-demo` (`retobj.files.map` render bug); **1.3.2** fixed that and
is the current `KIRI_GOOD`. `1.1.3` was the last good release before the streak.

Run locally with `act` (Docker-based): `act push -j cli-resolution`,
`act push -j templates`. `.actrc` is currently empty.

---

## Conventions

- **Docs and comments in English.** Commit messages are Québécois French (matches
  the rest of the Kirigami org).
- **Dev machine is Windows** (PowerShell) — mind path separators; the shell
  steps inside `action.yml` all run on `ubuntu-latest`, so keep them POSIX `sh`.
- **Stay lite.** This is deliberately a *composite* action, not a Node/Docker
  action — no `node_modules`, no build. Keep it that way unless there's a real
  reason not to.
- Pin third-party actions to a major tag (`@v7`), matching the current style.
- After changing `action.yml` inputs/behaviour, update `README.md`'s Inputs
  table and "What it does" list **and this file** in the same commit.
- `README.md` follows the shared Kirigami README template (centered logo block,
  tagline, MIT + Node badges, table of contents, License + Author sections) —
  keep it in sync with the other repos' READMEs.

---

## Known gaps / cleanup

- `LICENSE` reads `Copyright (c) 2026 Kirigami`; the rest of the ecosystem uses
  `MIT © Maxime Larrivée-Roy, 2026`.
- `README.md` links a non-existent `CONTRIBUTING.md`.
- `actions/checkout@v7` / `actions/setup-node@v7` are pinned ahead of what's
  released — verify these resolve on GitHub before relying on them.
- No caching of the global `@kirigami/kirigami` install on the fallback path.
- **`@kirigami/kirigami@latest` has been flaky** — the tests pin `env: KIRI_GOOD`
  (currently `1.3.2`) and a `latest-canary` scenario flags regressions. Bump
  `KIRI_GOOD` (or drop the pin) once `latest` has been reliably green.
- `cli-resolution`'s `pinned-version` scenario hardcodes `1.1.2`; bump it if that
  version is ever unpublished.

---

## License

MIT © Maxime Larrivée-Roy, 2026
