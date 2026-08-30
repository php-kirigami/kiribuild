# KiriBuild

Checkout, setup Node.js, install the [Kirigami](https://www.npmjs.com/package/@kirigami/kirigami) CLI, and export your project — in one step.

Uses your project's local `@kirigami/kirigami` dependency if it's installed (`node_modules/.bin/kiri`), and falls back to a global install otherwise. No `package.json` required.

## Usage

```yaml
- name: KiriBuild
  uses: php-kirigami/kiribuild@v1
  with:
    node-version: '24'
    lfs: 'true'
```

### Full example — deploy to GitHub Pages

```yaml
name: Deploy to GitHub Pages

env:
  TZ: America/Toronto

on:
  push:
    branches: ["main"]
  workflow_dispatch:

permissions:
  contents: read
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

      - name: KiriBuild
        uses: php-kirigami/kiribuild@v1
        with:
          node-version: '24'
          lfs: 'true'

      - name: Upload artifact
        uses: actions/upload-pages-artifact@v5
        with:
          path: dist

      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v5
```

## What it does

1. **Checkout** — checks out your repository (`actions/checkout@v7`), with optional Git LFS support.
2. **Setup Node** — installs the requested Node.js version (`actions/setup-node@v7`), with npm caching enabled.
3. **Install project dependencies** — runs `npm install`, but only if a `package.json` is present in the repo.
4. **Install Kirigami CLI (global fallback)** — installs `@kirigami/kirigami` globally, so the `kiri` CLI is always available even in repos without a `package.json`.
5. **Build and Export** — runs `kiri export` with the `--experimental-wasm-jspi` Node flag. It uses the project's local `kiri` binary (`node_modules/.bin/kiri`) if present, otherwise the global one.

## Inputs

| Name               | Description                                              | Required | Default  |
|--------------------|------------------------------------------------------------|----------|----------|
| `node-version`     | Node.js version to use                                    | false    | `24`     |
| `kirigami-version`  | Version of `@kirigami/kirigami` to install globally (fallback) | false    | `latest` |
| `lfs`              | Whether to checkout with Git LFS                           | false    | `false`  |

## Outputs

This action has no outputs. Artifact upload and deployment (e.g. `actions/upload-pages-artifact`, `actions/deploy-pages`) are left to your own workflow, so you stay in control of what happens to the exported files.

## Local testing

This action can be tested locally with [`act`](https://github.com/nektos/act), using the `.github/workflows/test.yml` workflow included in this repo:

```bash
act push -j test
```

See `CONTRIBUTING.md` (or the repo's workflow file) for details on the local Docker-based test setup.

## License

[MIT](./LICENSE) © Maxime Larrivée-Roy