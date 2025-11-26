# Help Assistant for Omeka S

Help Assistant adds Intro.js guided tours to the Omeka S admin interface. A help icon appears in the admin header and launches a page-specific tour defined by JSON. Tours can come from bundled files or custom mappings configured in the module settings, making it easier to onboard new users to key workflows (browse, add, and edit items, etc.).

## How it works

- The module injects Intro.js assets plus a small initializer (`asset/js/helpassistant-init.js`) into every admin controller. It records the current controller/action in `window.HelpAssistantContext`.
- The initializer renders a help icon next to the Omeka logo. Clicking it fetches `/admin/help-assistant/tours-map`, merges static tours with any saved in settings, and launches Intro.js.
- Tours are keyed by `Controller:action` (for example `Item:add`). If no tour exists, a generic message is shown. Redirect-aware steps allow a tour to continue across tabs/pages.

## Repository layout

```
HelpAssistant/
├── asset/css/helptour.css          # Light Intro.js styling tweaks and header layout
├── asset/js/helpassistant-init.js  # Injects the help icon and runs tours
├── asset/tours/                    # Built-in tour JSON and the tours map
├── config/module.config.php        # Routes (JSON map API, admin page) and forms
├── config/module.ini               # Module metadata and Omeka constraints
├── src/Module.php                  # Bootstraps assets and handles config form data
├── src/Form/                       # Admin settings form to add custom tour mappings
├── view/admin/help-assistant/      # Placeholder admin page for future UI
├── docker-compose.yml, Makefile    # Dev stack and helpers
└── test/, language/, vendor/, node_modules/ ...
```

## Add or edit tours

### Add a tour from the admin settings
1. In Omeka S, go to `Modules → Help Assistant → Configure`.
2. Add a row, choose the controller and action, and paste the tour JSON.
3. Save; the mapping is stored in Omeka settings and overrides any static tour with the same key.

### Tour JSON format

The module uses standard Intro.js options. A minimal example:

```json
{
  "showStepNumbers": false,
  "steps": [
    {
      "element": "#resource-template-select_field",
      "title": "Pick the template",
      "intro": "Load the fields your site already uses.",
      "position": "bottom",
      "redirect": "#item-media"  // optional: anchor or full URL to visit before next step
    }
  ]
}
```

Common fields: `steps` (array), `element` (CSS selector, optional for intro-only steps), `title`, `intro` (HTML allowed), `position`, and `redirect` (anchor or URL). When `redirect` is present, the module saves progress so the tour can resume on the next page.

## Development and Makefile

The repo ships with a Docker Compose stack (`erseco/alpine-omeka-s:develop` + MariaDB) and a Makefile to drive it. Port 8080 is exposed by default.

Core targets (run `make help` for the full list):
- `make up` / `make upd` — start the stack in foreground/background; browse http://localhost:8080
- `make down` / `make clean` — stop containers; `clean` also removes volumes
- `make logs`, `make shell`, `make fresh` — tail logs, open a shell in the Omeka container, or reset the stack
- `make enable-module` — install this module inside the container
- `make lint`, `make fix`, `make test` — PHP_CodeSniffer and PHPUnit workflows
- `make package VERSION=x.y.z` — build `HelpAssistant-x.y.z.zip` (temporarily bumps `config/module.ini`)
- `make i18n` — extract/update/compile translation templates and .mo files

## Requirements

- Omeka S 3.x or 4.x (see `config/module.ini`)
- PHP 7.4+ for development
- Docker Desktop 4+ and Make if you use the provided stack

## License

GPL-3.0-or-later. See `LICENSE`.

