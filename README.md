<div align="center">

<img src="https://zmotrin.github.io/assets/kirigami/kirigami-logo-universal.svg" alt="Kirigami" width="400" />

---

# KiriBuild

**The reusable GitHub Action for [Kirigami](https://github.com/php-kirigami/kirigami) projects — ensure Node 24+ and the `kiri` CLI, then export.**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE)
[![Node](https://img.shields.io/badge/node-%3E%3D24.0.0-brightgreen)](#requirements)

</div>

---

## Overview

**KiriBuild** is a lightweight [composite GitHub Action](https://docs.github.com/actions/creating-actions/creating-a-composite-action) that prepares the toolchain a Kirigami project needs and runs its production export:

- **Node 24+ on demand** — if the runner already has Node.js 24 or newer, it's left untouched; otherwise the requested version is installed.
- **Local CLI first** — uses your project's `@kirigami/kirigami` dependency (`node_modules/.bin/kiri`) when present, and installs `@kirigami/kirigami` globally only when it isn't.
- **One-command export** — runs `kiri export` with the WebAssembly flag `@kirigami/php-wasm` requires.

Checkout, Git LFS, and committing or deploying the exported files are **left to your workflow**, so you stay in control of what happens around the export.

---

## Table of contents

- [KiriBuild](#kiribuild)
  - [Overview](#overview)
  - [Table of contents](#table-of-contents)
  - [Requirements](#requirements)
  - [Usage](#usage)
  - [Full example — deploy to GitHub Pages](#full-example--deploy-to-github-pages)
  - [What it does](#what-it-does)
  - [Inputs](#inputs)
  - [Outputs](#outputs)
  - [Local testing](#local-testing)
  - [License](#license)
  - [Author](#author)

---

## Requirements

- A workflow that has already **checked out** the repository (`actions/checkout`).
- Network access to the npm registry (for the CLI install / project dependencies).

---

## Usage

```yaml
- name: Checkout
  uses: actions/checkout@v7

- name: KiriBuild
  uses: php-kirigami/kiribuild@v2
  with:
    node-version: '24'
```

---

## Full example — deploy to GitHub Pages

```yaml
name: Deploy to GitHub Pages

env:
  TZ: America/Toronto

on:
  push:
    branches: ["main"]
  workflow_dispatch:

permissions:
  contents: write
  pages: write
  id-token: write

concurrency:
  group: "pages"
  cancel-in-progress: true

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:

      - name: Checkout
        uses: actions/checkout@v7
        with:
          lfs: true

      - name: KiriBuild
        uses: php-kirigami/kiribuild@v2
        with:
          node-version: '24'

      - name: Commit exported files
        shell: bash
        run: |
          if [ -n "$(git status --porcelain)" ]; then
            git config user.name "kirigami[bot]"
            git config user.email "kirigami-bot@users.noreply.github.com"
            git add -A
            git commit -m "chore: update exported files [skip ci]"
            git push
          else
            echo "No changes to commit."
          fi

      - name: Upload artifact
        uses: actions/upload-pages-artifact@v5
        with:
          path: dist

      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v5
```

---

## What it does

1. **Check for Node 24+** — if the runner already has Node.js 24 or newer, nothing happens.
2. **Setup Node** — only when the check above fails, installs the requested Node.js version (`actions/setup-node@v7`).
3. **Install project dependencies** — runs `npm install`, but only if a `package.json` is present in the repo.
4. **Ensure the Kirigami CLI is available** — if `node_modules/.bin/kiri` exists it's used as-is; otherwise `@kirigami/kirigami` is installed globally so `kiri` is always on `PATH`.
5. **Export** — runs `kiri export` with the `--experimental-wasm-jspi` Node flag, preferring the project's local `kiri` binary over the global one.

---

## Inputs

| Name               | Description                                                                   | Required | Default  |
|--------------------|-----------------------------------------------------------------------------|----------|----------|
| `node-version`     | Node.js version to install if the runner does not already have Node 24+       | false    | `24`     |
| `kirigami-version` | Version of `@kirigami/kirigami` to install when the project has no local copy  | false    | `latest` |

---

## Outputs

This action has no outputs. Artifact upload, commits, and deployment are left to your own workflow, so you stay in control of what happens to the exported files.

---

## Local testing

`.github/workflows/test.yml` has two jobs, both on every push / PR:

- **`cli-resolution`** — runs the tiny fixture in
  [`test/fixtures/site`](./test/fixtures/site) through every CLI-resolution
  branch (`local-cli`, `global-cli`, `preinstalled`, `has-node-24`,
  `pinned-version`), and checks the core Kirigami feature surface on each:
  layouts, a data file, a Markdown block, a custom tag + render hook, and the
  image autogenerator.
- **`templates`** — runs the action against the official
  [`template-default`](https://github.com/php-kirigami/template-default) and
  [`template-demo`](https://github.com/php-kirigami/template-demo), covering the
  `@kirigami/canva` Sass pipeline, esbuild and the highlight plugin.

Run them locally with [`act`](https://github.com/nektos/act):

```bash
act push -j cli-resolution
act push -j templates
```

---

## License

This project is distributed under the [MIT license](./LICENSE).

---

## Author

MIT © Maxime Larrivée-Roy, 2026
