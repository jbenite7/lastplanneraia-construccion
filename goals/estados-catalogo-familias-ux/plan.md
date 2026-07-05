# Plan

## Solution Approach

Add a derived catalog-status layer instead of changing the meaning of `activa`. The app will keep existing schema behavior, but UI and semi-auto analysis will expose clear user-facing states: `Crea actividades`, `Se gestiona en Contratos`, `Es otro nombre de...`, `Necesita decisión`, and `No usar`.

The implementation should centralize this classification so Admin, semi-auto suggestions, Contratos, and PDC use the same vocabulary and do not re-infer status independently.

## Ordered Steps

1. Create a catalog status resolver.

   Files/systems:
   - Add `src/Support/FamilyCatalogStatusResolver.php` or equivalent shared support class.
   - Read from `general_pdc_familias`, `general_pdc_family_aliases`, `general_pdc_contractual_elements`, `general_pdc_activity_rules`, and `general_pdc_family_contract_options`.
   - Return structured fields: `status_key`, `label`, `reason`, `next_action`, `package_hint`, `canonical_family`, `admin_action`, `has_rules`, `has_contract_options`.

   Verification:
   - PHP unit/smoke test for each state: creates activities, managed in contracts, alias, needs decision, no use.
   - Include real examples: `PISOS_ENCHAPES`, `CAMPAMENTO`, one active family, one alias, one disabled/non-routable item.

2. Replace ambiguous Admin catalog status display.

   Files/systems:
   - `admin/src/Controllers/FamilyCatalogController.php`
   - `admin/views/pages/matching/family_catalog.php`

   Changes:
   - Pass derived status data to the view for every family, alias, and contractual element.
   - Replace `Activo/Inactivo` badges with user-facing labels and a short reason.
   - Add a full catalog report section/table showing item, type, status, reason, next action, and package/canonical family where applicable.
   - Keep technical raw fields available only as admin context; do not surface `activa=0` as the final explanation.

   Verification:
   - Extend `tests/test_admin_family_catalog_crud.php`.
   - Extend `tests/browser/admin-family-catalog.mjs` to assert the new labels and report.
   - Check CSV export if report/export is extended.

3. Add guided Admin option creation for families without contract packages.

   Files/systems:
   - `admin/public/index.php`
   - `admin/src/Controllers/FamilyCatalogController.php`
   - `admin/views/pages/matching/family_catalog.php`
   - Tables: `general_pdc_family_contract_options`, `general_pdc_family_contract_option_items`, optionally `general_dias_procesos_contratacion`.

   Changes:
   - Add an Admin-only form/action to create a contractual option for a selected family.
   - Capture modality, one or more package items, default quantities if represented in the existing model, and durations or duration references when required.
   - Validate family exists, package names are not blank, modality is valid, and duplicate option/items are handled idempotently.
   - Log `CatalogoFamilias` activity.

   Verification:
   - PHP test inserts a temporary family without options, uses the controller method to create an option, and verifies option/items exist.
   - Browser test creates or uses a temporary family and confirms the guided form works without SQL.

4. Improve semi-auto explanation payloads.

   Files/systems:
   - `src/Services/SemiAutoService.php`
   - `src/Support/SemiAutoQualityGate.php`
   - `src/Support/ActivityMatcher.php` only if needed to expose contractual/alias classification earlier.

   Changes:
   - Attach derived catalog status to suggestion analysis when a family is detected, missing options, routed to contracts, or classified as no-match.
   - For cases like `Campamento de Obra`, avoid generic `Sin familia detectada` when the text maps to an active contractual element. Show `Se gestiona en Contratos`, package hint, and next action.
   - For cases like `Pisos y Enchapes`, show `Crea actividades` plus `Falta opción contractual` and next Admin action.

   Verification:
   - Extend semi-auto PHP tests for `Pisos y Enchapes` and `Campamento de Obra`.
   - Assert response JSON includes `analysis.catalog_status` or equivalent structured status.

5. Update semi-auto UI and role visibility.

   Files/systems:
   - `public/js/modules/semi_auto_review.js`
   - Existing CSS if needed in `public/css/styles.css`.

   Changes:
   - Render status label, clear reason, package hint, and next action in suggestion cards/details.
   - Keep technical detail hidden from non-admin users; Admin may expand technical data.
   - Do not show catalog-global actions to end users. End users can only understand and assign packages in Contratos where they already have permissions.

   Verification:
   - Extend `tests/browser/semi-auto-review.mjs` and/or `tests/browser/auto-definir-contratos.mjs`.
   - Browser assertions: no raw `activa`, no generic `Inactivo` as final reason, new labels are visible.

6. Keep Contratos manual assignment ergonomic.

   Files/systems:
   - `src/Controllers/Api/ContratosApiController.php`
   - `views/contratos/contratos.view.php`

   Changes:
   - Preserve the current fix that lists rows even before packages are assigned.
   - Ensure rows with missing package options explain whether the next step is manual package assignment or Admin option creation.
   - Do not expose Admin catalog mutation actions to non-admin users.

   Verification:
   - Existing focused PHP checks.
   - Browser check in Da Porto week/context 8: `Pisos y Enchapes` appears in Contratos and package selectors populate after modality selection.

7. Verify PDC from a clean module context.

   Files/systems:
   - `tests/browser/last-planner-two-week-cycle.mjs` or a new focused browser spec.
   - `tests/browser/support/session.mjs`
   - Existing `/listado-actividades`, `/contratos`, `/pdc` APIs.

   Changes:
   - Build an E2E scenario that starts from a clean Listado/Contratos/PDC state for a test project or isolated week/context.
   - Do not assume dependency on `semanas_activas`; use the module week/context that these pages actually consume.
   - Flow: generate/confirm Listado suggestions, assign or auto-define Contratos, generate PDC, and assert messages guide the user through blocked/manual cases.

   Verification:
   - Playwright E2E with screenshots/evidence for Listado, Contratos, PDC, and Admin catalog.
   - Confirm `Pisos y Enchapes`, `Campamento de Obra`, and at least one normal active family are covered.

8. Add catalog-wide report verification.

   Files/systems:
   - New or extended PHP test around the resolver/report query.
   - Optional exported CSV route if report export is required.

   Changes:
   - Assert every row in families, aliases, and contractual elements has a non-empty status label, reason, and next action.
   - Assert no report row falls back to ambiguous `Inactivo` when a derived explanation exists.

   Verification:
   - `docker compose exec app php tests/test_admin_family_catalog_crud.php`
   - New focused test, for example `tests/test_family_catalog_status_resolver.php`.

## Verification Commands

- `docker compose exec app php -l src/Support/FamilyCatalogStatusResolver.php`
- `docker compose exec app php -l admin/src/Controllers/FamilyCatalogController.php`
- `docker compose exec app php -l src/Services/SemiAutoService.php`
- `docker compose exec app php tests/test_admin_family_catalog_crud.php`
- `docker compose exec app php tests/test_auto_definir_contratos.php`
- `docker compose exec app php tests/test_semi_auto_da_porto_feedback.php`
- `docker compose exec app php tests/test_semi_auto_service.php`
- `npx playwright test tests/browser/admin-family-catalog.mjs`
- `npx playwright test tests/browser/auto-definir-contratos.mjs`
- `npx playwright test tests/browser/semi-auto-review.mjs`
- New focused E2E for clean Listado -> Contratos -> PDC flow.

## Risks And Open Questions

- The goal is intentionally broad and should be implemented carefully in small internal commits or checkpoints even if delivered as one goal.
- `activa=0` must not be redefined globally; it still controls matcher behavior. The new user-facing state should be derived.
- Creating contract options through Admin must be idempotent and must not duplicate existing package items.
- PDC clean-flow tests can be brittle if they mutate shared project data; prefer a temporary fixture or explicit setup/cleanup.
- Admin-only technical detail must respect current role checks; non-admin users should not see global catalog actions.
