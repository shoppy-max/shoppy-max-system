# Copilot coding instructions for Shoppy Max

Before making changes, read:

- `AGENTS.md` (operator-level workflow and invariants)
- `README.md` (user-facing behavior and setup)
- `routes/web.php` plus the relevant controller/service entry points for the module you touch

## Project-specific requirements

- Preserve business invariants for purchases, GRN, inventory units, orders, reseller flows, and courier settlements.
- Reuse existing service-layer entry points for high-risk transitions:
  - `app/Services/InventoryUnitService.php`
  - `app/Services/CourierPaymentOrderService.php`
- Keep SQL/query behavior compatible with both SQLite and MySQL.
- Make the smallest possible focused change; do not refactor unrelated areas.

## Validation expectations

- Run existing checks relevant to your change:
  - `php artisan test`
  - `npm run build`
  - `./vendor/bin/pint`
- If workflow/status logic is changed, verify the full affected flow (not syntax only), following `AGENTS.md`.

## Safety

- Do not commit secrets or edit `.env` for permanent behavior changes.
- Do not bypass existing controller/service business rules with ad-hoc SQL status updates.
- Do not modify sensitive `server_utils/` files unless explicitly required.
