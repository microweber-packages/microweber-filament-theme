# microweber-filament-theme — architecture & where-to-edit guide

The CSS/JS theme for Microweber's **Filament v5** admin panels (`admin`, `checkout`,
`profile`, `vendor`). Tailwind v4 engine, a bespoke `build.cjs`, ~28k lines of CSS
across ~156 files. This file is the map — read it before editing.

> **Golden rule:** the compiled bundle is `resources/dist/build/microweber-filament-theme.css`
> (also copied to `public/vendor/microweber-packages/microweber-filament-theme/build/`).
> Nothing you edit shows up until you **rebuild** (`npm run build`). The dev server
> serves the built file, never the source.

---

## 1. Where do I edit X? (decision table)

| I want to change… | Edit | Notes |
|---|---|---|
| A colour / spacing / radius / font token | `resources/assets/css/tokens/*.css` | Single source of truth. Light + dark (`.dark`) both live here. Change the token, not every call site. |
| A Filament component (button, table, modal, form, tabs, badge, sidebar, …) | `resources/assets/css/filament/<area>/…` | Per-component overrides mirroring Filament's own layout. |
| General admin look (the big "v2 reskin", dashboard, dialogs, media manager) | `resources/assets/css/microweber/general/NN-*.css` | **Split parts** — see §4. Do NOT edit `general-styles.css` directly. |
| Mobile / touch behaviour | `resources/assets/css/microweber/mobile/NN-*.css` | **Split parts.** Do NOT edit `mobile-touch.css` directly. |
| Responsive breakpoints (topbar fit, gutters) | `resources/assets/css/filament/responsive.css` | Blocks at `≤768px`, `≤600px`, `≤480px`. |
| Live-Edit UI, pickers, ESE, vendored CSS | `resources/assets/css/microweber/{live-edit-*,linkpicker,element-style-editor,…}.css` | **Scope creep** — this really belongs to `frontend-assets` / `microweber-live-edit` (see §8). |
| Dark-mode specifics | Prefer a token in `tokens/colors.css` `.dark` block. Avoid `filament/dark.css` (legacy hardcoded leftovers). |

If you're not sure which file owns a selector: `grep -rn '\.fi-btn' resources/assets/css/`.
Today a component is often styled across **several** files (see §7) — grep first.

---

## 2. The cascade order (why your rule may lose)

`microweber-filament-theme.css` → `index.css`, which imports in this exact order
(**later wins** at equal specificity; everything is unlayered so it beats Filament's
`@layer` rules by origin):

```
external.css   (Tailwind + stock Filament theme + 5 frontend-assets bundles)
tokens/        (colors, breakpoints, sizes, typography)
filament/      (per-component overrides — incl. responsive.css, dark.css)
base.css
microweber/    (index.css → general-styles, mobile-touch, live-edit-*, admin/…)
admin.css
global.css     ← loads LAST, so it wins ties
```

`microweber/index.css` order (abridged): `general-styles` → `tomselect` → `grid-only`
→ `buttons` → `live-edit-classes` → `element-style-editor` → `mobile-touch` → `admin/`.
So **`mobile-touch` loads after `general-styles`** (deliberate — its `!important`
mobile floors must win equal-specificity battles).

**Practical consequence:** if your rule "doesn't take", a later file is overriding it.
Options, in order of preference: (a) edit the rule in the file that already owns the
component; (b) raise specificity (`body.fi-panel-admin .fi-x` is `0,2,0`); (c) move your
rule later; (d) `!important` as a last resort. The theme has **~3,200 `!important`** —
most are self-inflicted ties, not fights with Filament. Don't add more reflexively.

---

## 3. Panels & scoping

- Body classes: `body.fi-panel-admin` / `.fi-panel-checkout` / `.fi-panel-profile` /
  `.fi-panel-vendor`. RTL is `[dir="rtl"]`.
- **`build.cjs → broadenPanelScope()`** runs on the final flattened CSS: every
  `body.fi-panel-admin …` rule is **cloned** for `fi-panel-profile` + `fi-panel-vendor`
  so those panels inherit the admin styling. The source is never edited (contract tests
  read the source). If profile/vendor look unstyled, check this ran (build log:
  `broadenPanelScope: shared N admin rule-clones`).

---

## 4. Generated monoliths — EDIT THE PARTS

`general-styles.css` (6k lines) and `mobile-touch.css` (2.6k lines) were split into
per-concern parts for navigability:

```
microweber/general/01-primitives-overrides.css … 12-v2-admin-reskin.css   (+ README)
microweber/mobile/01-root-tokens.css … 14-media-pagination-selects.css     (+ README)
```

**The monolith files are GENERATED** — `build.cjs → regenerateSplitMonoliths()`
re-concatenates the sorted parts (byte-exact) before every build, because
`microweber/index.css` still `@import`s the monoliths and ~80 contract tests read
their bytes. So:

- **Edit the `NN-*.css` parts, never `general-styles.css` / `mobile-touch.css`.**
- `ThemeCssSplitMonolithDriftContractTest` fails if a monolith ≠ `concat(parts)`.
- Add a concern = add a new `NN-<slug>.css` (the number sets order), then build.

---

## 5. Build & verify

```bash
cd packages/microweber-filament-theme
npm run build        # regenerates monoliths → webpack JS → Tailwind CSS → broadenPanelScope → copy to public/
```

**Verify a CSS change** (the compiled output legitimately changes, so there's no md5
shortcut for a real edit):

1. `php artisan serve --port=8000` (or it's already up) and open the admin.
2. Eyeball the surfaces you touched in **light AND dark** (toggle via the user menu, or
   `localStorage.theme='dark'`), at **desktop AND phone widths** (resize to ~390px).
   Admin pages render fine under Playwright on `:8000` (log in; the session persists).
3. For subtle changes, compare `getComputedStyle` of the affected selectors before/after
   rather than trusting the eye (see the probe recipe in the memory
   `theme-css-monolith-split`).
4. Run the touched contract suites: `bash run-tests.sh Core` / the relevant
   `tests/Feature/*ContractTest.php`.

There is **no watch mode** (`npm run dev` === `npm run build`). Rebuild after every edit.

---

## 6. Conventions

- **Colours:** use tokens. Primary scale is **comma-RGB triplets** → `rgb(var(--primary-500))`
  / `rgba(var(--primary-500), .5)`. A bare `var(--primary-500)` paints **nothing**.
  Neutral surfaces/text: `var(--mw-bg-surface)`, `var(--mw-text-primary)`, etc. — these
  differ light vs dark, so only use them where the element *should* follow the theme.
  A fixed design colour (a navy button that stays navy in dark) must stay a literal.
- **Admin button colours are a product decision** (green SAVE / navy New+Add /
  navy-outline Save&LiveEdit) — don't "normalize" them to the primary.
- **Dark mode:** consume tokens so it auto-switches. If a dark rule must be selector-
  specific, split **sizing (plain class)** from **colour (compound `.dark` selector)**.
- **Touch targets** ≥ 44×44px on interactive elements.
- **Currency:** never hardcode `$`.

---

## 7. Known rough edges (the maintainability debt)

These make edits harder than they should be — tracked for the refactor (§9):

- **`.fi-*` fragmentation:** the same component is restyled across 6–10 files (e.g.
  `.fi-btn` in ~15, `.fi-ta-*` across ~32). There is no single owner, so you grep, and
  the "winner" is whichever loads last. This is why a rule "doesn't take".
- **Dark mode defined 3×:** `tokens/colors.css` `.dark` (correct) + `filament/dark.css`
  (843 lines of hardcoded `!important` leftovers) + ~scattered `.dark` rules in the
  `general/` parts.
- **`!important` wars:** ~3,200, mostly fighting the theme's *own* siblings, not Filament.
- **Scope creep:** ~1/3 of the CSS styles Live-Edit / mw-ui / vendored (Bootstrap grid,
  TomSelect) — DOM that lives in *other* packages (`frontend-assets` / `microweber-live-edit`)
  on a *different* build (Vite). Same component forks across two bundles → drift.

---

## 8. Refactor status & roadmap

**Done:** mechanical split of the two monoliths (navigable parts, generated back,
compiled output byte-identical); dead-file/dep removal; a11y-bundle + v3 barrel retirement.

**Planned (behind gates — compiled-md5 for neutral changes, else contract tests +
computed-style probes + light/dark screenshots):**
1. Collapse the triple dark-mode definition toward `tokens/colors.css` only.
2. Single-source-of-truth per `.fi-*` component (kill the 6–10-way fragmentation).
3. Hardcoded hex → token (exact-equality only; mind the dual-context trap — a token
   that differs in dark can't replace a fixed design colour).
4. **Keystone:** wrap all theme CSS in one `@layer mw-theme` declared after Filament's
   layers, so intra-theme *source order* decides and most `!important` becomes removable.
   Highest leverage for "easy to edit", highest risk (cascade reverses for `!important`
   across layers) — do it piloted on one component, probe-gated, with sign-off.
5. Relocate Live-Edit / vendor CSS to its owning package (ends the cross-package fork).

Full slice-by-slice plan + grep-verified numbers: the session scratchpad
`important-reduction-plan.md`. Risk notes live in the memory
`theme-css-monolith-split`.

---

## 9. Key files

- `build.cjs` — the build (monolith regen, SCSS compile, Tailwind run, `broadenPanelScope`, publish).
- `resources/assets/css/index.css` — the import order (the cascade).
- `resources/assets/css/tokens/` — the single source of truth for design values.
- `resources/assets/css/filament/responsive.css` — mobile breakpoints (768/600/480).
- `resources/assets/css/microweber/general/` + `mobile/` — the split parts (edit here).
- `MwColors.php` (in `MicroweberPackages/Filament/Themes/`) — PHP colour authority for
  Filament panels; brand primary is Bootstrap blue `#0d6efd`.
</content>
