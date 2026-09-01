![Logo](github.png)

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/StudioWaaz/SyliusFedexPlugin/build.yml?style=for-the-badge)
![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/quality/g/StudioWaaz/SyliusFedexPlugin?style=for-the-badge)

# WaazSyliusFedexPlugin

This plugin allows you to integrate **FedEx REST API** into your Sylius store for shipping labels generation and pickup points / drop-off locations.

## Features

- **FedEx Shipping Labels Export** (via `bitbag/shipping-export-plugin`)
  - Integration with FedEx REST API v1 (`/ship/v1/shipments`)
  - Generates labels in PDF or PNG format
  - Automatically retrieves and assigns master tracking numbers
- **FedEx Pickup Points / Drop-off Locations** (via `setono/sylius-pickup-point-plugin`)
  - Searches nearest FedEx locations and drop-off points (`/location/v1/locations`)
- **OAuth2 Token Handling** with automatic caching
- **Sylius 1.11, 1.12 & 1.13 compatible** with native Symfony HTTP client (`symfony/http-client`)

---

## Installation

### 1. Install via Composer

```bash
composer require waaz/sylius-fedex-plugin
```

### 2. Enable the Plugin

Add the plugin to your `config/bundles.php`:

```php
return [
    // ...
    BitBag\SyliusShippingExportPlugin\BitBagSyliusShippingExportPlugin::class => ['all' => true],
    Setono\SyliusPickupPointPlugin\SetonoSyliusPickupPointPlugin::class => ['all' => true],
    Waaz\SyliusFedexPlugin\WaazSyliusFedexPlugin::class => ['all' => true],
];
```

### 3. Plugin Configuration

Create `config/packages/waaz_sylius_fedex_plugin.yaml`:

```yaml
waaz_sylius_fedex:
    sandbox: true      # true for sandbox/testing, false for production
    weight_unit: 'KG'  # 'KG' or 'LB'
```

### 4. Enable FedEx Pickup Point Provider (Optional)

In `config/packages/setono_sylius_pickup_point.yaml`:

```yaml
setono_sylius_pickup_point:
    providers:
        fedex: true
```

---

## Shipping Gateway Configuration (Admin)

In Sylius Admin panel:
1. Go to **Shipping > Shipping gateways** (`/admin/shipping-gateways/new/fedex`).
2. Fill in your FedEx credentials:
   - **FedEx API Key (Client ID)**
   - **FedEx Secret Key**
   - **FedEx Account Number**
   - **Environment** (`sandbox` or `production`)
   - **Default Shipping Service** (`FEDEX_GROUND`, `FEDEX_EXPRESS_SAVER`, `INTERNATIONAL_PRIORITY`, etc.)
   - **Drop-off Type** (`REGULAR_PICKUP`, `BUSINESS_SERVICE_CENTER`, `DROP_BOX`, etc.)
   - **Packaging Type** (`YOUR_PACKAGING`, `FEDEX_BOX`, `FEDEX_ENVELOPE`, etc.)
   - **Label format** (`PDF` or `PNG`) and **Stock type** (`PAPER_4X6`, `PAPER_8.5X11_TOP_HALF_LABEL`, etc.)
   - **Shipper Contact and Address details**

---

## Running Tests

- **PHPUnit (Unit Tests)**:

```bash
vendor/bin/phpunit
```

- **Behat (non-JS scenarios)**:

```bash
vendor/bin/behat --strict --tags="~@javascript"
```

- **Behat (JS scenarios)**:

```bash
# 1. Start Chrome headless
google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1 &

# 2. Start test application web server
APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon

# 3. Run Behat
vendor/bin/behat --strict --tags="@javascript"
```

- **PHPStan**:

```bash
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
```

- **Coding Standard (ECS)**:

```bash
vendor/bin/ecs check src
```

---

## Author

- [@ehibes](https://www.github.com/ehibes) for [Studio Waaz](https://www.studiowaaz.com)

## License

This plugin's source code is completely free and released under the terms of the MIT license.
