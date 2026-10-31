# Zero_QuickView

"Quick view" on the product cards of category, search and brand pages: the
product in a dialog without leaving the list. The theme's `<dialog>` and
HTMX; no script of its own.

## Install

Requires Magento Open Source 2.4.7–2.4.9 and PHP 8.2–8.4 with the GD extension built with WebP (`php -r 'var_dump(function_exists("imagewebp"));'` says `true`; Magento's own PHP requirements cover the rest). Without WebP in GD, Zero skips its WebP image copies and serves the originals.

```bash
composer require zerofront/module-zero-quick-view
bin/magento setup:upgrade
bin/magento setup:di:compile           # production mode
bin/magento setup:static-content:deploy # production mode
bin/magento cache:flush
```

Composer installs `zerofront/module-zero-core` with it.

Part of [`zerofront/zero`](https://zerofront.dev), which installs the Zero theme and every Zero module at once.

## On the storefront

- **The button** sits on the card's photo (top end corner): with a mouse it
  shows on hover and keyboard focus, on a touch screen always. The Zero
  theme's product list renders the block `zero.quickview.button` on every
  card, as it does the badges.
- **The dialog** (`data-dialog-open`, the theme's modal) loads the product
  from `quickview/product/view?id=…` (`hx-get`, "Loading…" while it comes):
  photo, name, the price as lists show it (special price, "As low as"),
  stock, a short summary, View full details.
- **Add to Cart** right in the dialog for a product with nothing to choose
  (a plain POST to Magento's cart: the shopper lands back on the list with
  the usual message); a product with options (sizes, colours, required
  custom options, bundles) gets "Choose options", a link to its page.
- Esc, the close button or a click outside closes it.

The fragment is never cached (it carries the visitor's form key and their
group's price) and answers only for a product the store view lists
(enabled, on its website, visible in the catalog or in search; a "Not
Visible Individually" product is refused). A quote-only product
(Zero_QuoteRequest) gets "Choose options", never a direct Add to Cart.

## Settings

Stores > Configuration > Zero > Quick View: on or off (off by default).

## Tests

`dev/tests/zero-e2e/tests/quick-view.spec.ts`: open, add to cart from the
dialog, a product with options, Esc.

What changed in each release: [CHANGELOG.md](CHANGELOG.md).

## License

OSL-3.0 or AFL-3.0, your choice: see `NOTICE.md`.

---

<a href="https://vilmate.com"><picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/zerofrontdev/zero/main/docs/images/vilmate-logo-dark.svg">
  <img src="https://raw.githubusercontent.com/zerofrontdev/zero/main/docs/images/vilmate-logo.svg" alt="Vilmate" height="34" align="right">
</picture></a>

Zero is made by [Fixstacks](https://fixstacks.com) in partnership with [Vilmate](https://vilmate.com), a software development company.
Documentation: https://zerofront.dev/docs. Demo store: https://demo.zerofront.dev.
