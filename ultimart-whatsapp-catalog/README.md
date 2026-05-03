# Ultimart WhatsApp Catalog

This plugin gives you a WordPress shortcode that shows your products, lets visitors choose quantity with `+ / -`, and sends the order to WhatsApp.

## Install

1. Zip the folder `ultimart-whatsapp-catalog`
2. In WordPress admin go to `Plugins > Add New > Upload Plugin`
3. Upload the zip and activate it

## Use

Create a page and paste this shortcode:

```text
[ultimart_products whatsapp="8801XXXXXXXXX"]
```

Replace `8801XXXXXXXXX` with your WhatsApp number.

## What it includes

- 4 products from your text content
- Product click to show details
- Quantity `+ / -`
- WhatsApp order button

## If you want to change products

Edit this file:

- `ultimart-whatsapp-catalog.php`

The product data is inside the `get_products()` function.
