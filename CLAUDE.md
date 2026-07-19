# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`acetonesoft/acetone-api-php` is a thin PHP client for the AceTone.ai REST API. It covers all
three public endpoints: background removal/manipulation (`remove/background`), object removal by
mask (`remove/object`), and image enhance/upscale (`enhance/image`). Requires PHP >= 7.4 and
depends on `guzzlehttp/guzzle`. The authoritative API spec is `https://api.acetone.ai/openapi.json`.

## Commands

```bash
# Install dependencies
composer install

# Run all tests (phpunit.xml is auto-discovered from the project root)
./vendor/bin/phpunit

# Run a single test method
./vendor/bin/phpunit --filter testColors
```

`phpunit.xml` sets `tests/bootstrap.php` as the bootstrap, which autoloads and loads `.env`.

**Tests hit the live API and need a real API key.** Copy `.env.sample` to `.env` and set
`ACETONE_API_KEY=<your key>` (the `00000000-0000-0000-0000-000000000000` placeholder is
rejected; without a key the API tests are skipped, not failed). `.env` is git-ignored and read
by `acetone_load_env()` in `env.php` (a real shell/CI env var wins over the file). Tests verify
results by inspecting output pixels via GD, so the `ext-gd` extension is required.

The interactive demo at `demo/index.php` reads the same `ACETONE_API_KEY` (it loads `.env` via
`env.php` too).

## Architecture

All logic lives in a single class, `src/Acetone/AcetoneApi.php` (plus `AcetoneException.php`).
PSR-4 maps `AcetoneSoft\Acetone\` → `./src/Acetone`. `src/autoload.php` is a standalone
autoloader (reads `composer.json` PSR-4) for use without Composer; it includes
`vendor/autoload.php` first when present.

`AcetoneApi` exposes two API styles:

1. **Fluent builder** — set the source with `fromFile()` / `fromUrl()` / `fromString()` /
   `fromBase64()`, chain modifiers, then call a terminal getter/saver. Modifiers:
   - background: `bgRemove`, `bgColor`, `bgGradient`, `bgRadialGradient`, `bgBlur`,
     `bgGrayscale`, `bgImage`, `bgImageFile`, plus `size()` / `crop()`, `shadow()`,
     `logoImage()` / `logoImageFile()`, `quality()`, `exact()`, and generic `options()`
   - object removal: `mask()` / `maskFile()`, `objectBgColor()`
   - enhance: `targetImage()` / `targetImageFile()`, `enhanceMode()`

   Terminals, one per endpoint: `get()` / `save()` → `remove/background`;
   `getObject()` / `saveObject()` → `remove/object`; `getEnhanced()` / `saveEnhanced()`
   → `enhance/image`. Each getter takes an optional `format`; each saver derives it from the
   filename extension.
2. **One-shot methods** — `backgroundRemove`, `backgroundColor`, `backgroundGradient`,
   `backgroundRadialGradient`, `backgroundBlur`, `backgroundGrayscale`, `backgroundImage`,
   `backgroundReplace`, `objectRemove`, `enhanceImage`. These are wrappers around the fluent
   chain that take the source image as a binary string.

Key flow:

- Modifiers only accumulate state — options go into `$this->options` (keyed by `bg_mode` plus
  parameters), binaries into `$this->imageBin` / `$this->bgImageBin` / `$this->logoImageBin` /
  `$this->maskBin` / `$this->targetImageBin`. **No HTTP request is made until a terminal getter.**
- All three terminals delegate to the private `_run(endpoint, extraFiles, format)`, which builds
  the multipart request (`image` plus any non-null extra file parts like `bgimage`, `logoimage`,
  `mask`, `target_image`), validates the output format (`jpg|jpeg|png|webp`, with `jpg`
  normalized to `jpeg`), then calls `action()`. Savers delegate to the private `_put()`.
- `action()` is the **only HTTP touchpoint**: a POST to the endpoint relative to
  `baseUrl = https://api.acetone.ai/api/v1/`, with the API key in the `token` header and
  `$this->options` sent as a query string via `http_build_query`. It records elapsed time
  (`getTime()`).
- Helpers: `_color()` normalizes a color (array, `"r,g,b"` string, or hex `#fff` / `#ffffff` /
  no `#`) into an `"r,g,b"` string (used for `bg_colors`); `_colorHex()` normalizes the same
  inputs into a `"#rrggbb"` string (used for `shadow_colour` and object-removal `bg_color`);
  `_readFile()` reads a file/URL with descriptive errors; `_tmpName()` generates a temp filename
  for a multipart part.
- Library-level errors throw `AcetoneException` (extends `RuntimeException`); transport errors
  surface as `GuzzleException`.

## Gotchas

- The namespace is `AcetoneSoft\Acetone` (older docs elsewhere may show `avadim\Acetone`).
- Tests require a live API connection, a valid key, and `ext-gd`; they are not hermetic unit tests.
  Without `ACETONE_API_KEY` they are skipped rather than failed.
- Color formats differ per field: `bg_colors` takes `"r,g,b"` (via `_color()`), but the shadow
  (`shadow_colour`) and object-removal fill (`bg_color`) require hex `"#rrggbb"` (via `_colorHex()`).
  The live API rejects `"r,g,b"` for the latter two with `{"detail":"unknown color specifier: ..."}`
  (returned as HTTP 200 + JSON, not an error status).
