---
name: admin-help-tours
description: "Change contextual admin tours, tour mappings, redirects, or Intro.js integration."
---

# Contextual admin tours

Read root `Module.php` (not `src/Module.php`), `src/Form/`, `src/Controller/`,
`asset/js/helpassistant-init.js`, and `test/js/helpassistant-init.test.js`.

- The layout listener is broad, but asset loading is admin-scoped. Do not enqueue tour tooling on public sites.
- Resolve tours from the current controller/action and the tours-map endpoint. Preserve bundled defaults,
  custom mappings, and validation of uploaded or configured tour definitions.
- Cross-page steps need the existing redirect/resume flow. Missing targets and navigation timing must not
  trap the user, restart endlessly, or require arbitrary fixed sleeps.
- Keep close/skip controls and keyboard access usable. Treat tour content and selectors as configuration,
  not executable code; preserve sanitization at the server boundary.

Run PHP tests for changed mapping/controller paths and `npm ci` followed by `npm run test:js` for the
browser logic. Exercise a single-page tour, a redirected step and a missing target in the real admin UI
when behavior changes; Jest does not prove Intro.js works with the rendered Omeka page.
