# Ultimart WhatsApp Catalog

This version uses the structure below:

- Page 1: product list
- Page 2: single product detail + quantity + shipping form
- Same detail page can serve 4 separate direct product URLs
- Orders save in the WordPress database
- Admin order screen: `WordPress Admin > আল্টিমার্ট অর্ডার`

## Current catalog

This package currently includes 4 products:

- `binbond-silver` = BINBOND লাক্সারি ঘড়ি সিলভার হোয়াইট
- `binbond-blue` = BINBOND লাক্সারি ঘড়ি রয়্যাল ব্লু
- `binbond-black` = BINBOND লাক্সারি ঘড়ি মিডনাইট ব্ল্যাক
- `ultimart-combo` = দ্য আলটিমেট জেন্টলম্যান ৩-ইন-১ লাক্সারি কম্বো

Current combo pricing:

- Offer price: `999` টাকা
- Regular price: `1350` টাকা

Current combo items:

- ট্রেন্ডি ওভাল সানগ্লাস
- আরমানি লেদার ওয়ালেট
- CURREN লাক্সারি ওয়াচ

## Shortcodes

### Product list page

```text
[ultimart_product_list detail_page="/order" title="আমাদের পণ্যসমূহ"]
```

`detail_page` should be the URL path of your detail page.

Example:

- If your detail page slug is `order`
- Then use `detail_page="/order"`

### Home page video

Put this shortcode at the start of the Home page only:

```text
[ultimart_campaign_video]
```

Use only the shortcode on the page. Do not paste the YouTube URL separately in the page content.

To use a different video:

```text
[ultimart_campaign_video video_url="https://youtube.com/shorts/ugawiWnehts?feature=share"]
```

Do not put this video shortcode on the Shop page if you want video only on Home.

### Product detail page

```text
[ultimart_product_detail whatsapp="8801XXXXXXXXX" back_page="/shop"]
```

Replace `8801XXXXXXXXX` with your WhatsApp number.

## Recommended setup

1. Create a page named `Shop`
2. Put this shortcode there:

```text
[ultimart_product_list detail_page="/order"]
```

3. Create another page named `Order`
4. Put this shortcode there:

```text
[ultimart_product_detail whatsapp="8801XXXXXXXXX" back_page="/shop"]
```

Now customers will:

1. open the Shop page
2. tap anywhere on a product card
3. go to the Order page
4. select quantity
5. fill shipping address and phone
6. save the order

## WordPress setup if Shop and Order already exist

If you already created `Shop` and `Order`, keep them. You only need to update
the shortcode and optionally add two new category pages.

### Existing Shop page

Use this shortcode on the `Shop` page for all products:

```text
[ultimart_product_list detail_page="/order"]
```

Optional: put the video shortcode above the product shortcode if you want the
video and products on the same page:

```text
[ultimart_campaign_video]
[ultimart_product_list detail_page="/order"]
```

### Existing Order page

Use this shortcode on the `Order` page:

```text
[ultimart_product_detail whatsapp="8801XXXXXXXXX" back_page="/shop"]
```

Replace `8801XXXXXXXXX` with your WhatsApp number.

### New Watch page

Create a new page named `Watch` and use:

```text
[ultimart_watch_list detail_page="/order"]
```

This page shows only watch products.

### New Combo page

Create a new page named `Combo` and use:

```text
[ultimart_combo_list detail_page="/order"]
```

This page shows only the combo product.

### Recommended menu links

Add these pages to your WordPress menu:

- `Shop` = all products
- `Watch` = only watches
- `Combo` = only combo offer

Do not add `Order` to the main menu. Customers should reach the order page by
clicking a product card.

### Current order page behavior

The `Order` page now includes:

- product details
- quantity selector
- delivery charge option
- subtotal
- total price
- shipping address
- note box for bKash/advance payment information

Delivery charges:

- Dhaka city: `60` taka
- Outside Dhaka: `130` taka

### Admin order screen

Go to:

```text
WordPress Admin > আল্টিমার্ট অর্ডার
```

Admin order actions:

- `Accept`
- `Delivered`
- `Product Back`
- `Pending`
- `Cancel`

The admin screen also shows delivered order totals:

- delivered order count
- delivered product subtotal
- delivered delivery charge
- delivered total collection

## Direct product links

You do not need 4 separate WordPress pages.

Keep 1 detail page and use direct URLs like these:

- `/order/?ultimart_product=binbond-silver`
- `/order/?ultimart_product=binbond-blue`
- `/order/?ultimart_product=binbond-black`
- `/order/?ultimart_product=ultimart-combo`

This means:

- customers can come from the Shop page
- or you can send a direct order link for a single product

If your detail page slug is different, replace `/order/` with your own page slug.

Example combo direct order link:

- `/order/?ultimart_product=ultimart-combo`

## Images

The plugin supports 2 image methods.

### Method 1: local plugin files

Put these files in:

- `assets/images/product-1.jpeg`
- `assets/images/product-2.jpeg`
- `assets/images/product-3.jpeg`
- `assets/images/product-4.jpeg`

### Method 2: WordPress Media Library URLs

Open `ultimart-whatsapp-catalog.php`

Inside `get_products()`, each product has:

```php
'image_url' => '',
```

Paste the full image URL there after uploading the image to WordPress Media Library.

If `image_url` is set, the plugin uses that image first.

## Orders

Orders are stored in a custom database table created by the plugin.

To see saved orders:

- `WordPress Admin > আল্টিমার্ট অর্ডার`

Pending orders can be accepted from the order table. Use `Accept` to mark an
order as accepted, `Cancel` to reject it, or `Pending` to move it back to the
waiting state.

## Header and footer update

This plugin controls only the product/order shortcode area. The site header and
footer usually come from your WordPress theme or page builder.

Common ways to update them:

1. `WordPress Admin > Appearance > Customize`
2. `WordPress Admin > Appearance > Editor` for block themes
3. `WordPress Admin > Templates / Elementor / Header Footer Builder` if the site uses Elementor or a builder plugin
4. Edit the WordPress page content if the header/footer is manually placed inside the page

For this plugin's campaign text:

- Main list heading/subtitle is in `ultimart-whatsapp-catalog.php`
- Product names, prices, features, delivery text are inside `get_products()`
- Design and product list styles are in `assets/style.css`
- Product detail/order interactions are in `assets/app.js`

## Install ZIP in WordPress

1. Go to `WordPress Admin > Plugins > Add New Plugin`
2. Click `Upload Plugin`
3. Select the ZIP file: `ultimart-whatsapp-catalog.zip`
4. Click `Install Now`
5. Activate the plugin

## Manual local testing

This is a WordPress plugin, so it does not run by opening the PHP file directly.

To test it manually:

1. Start a local WordPress site using `LocalWP`, `XAMPP`, `Laragon`, or any PHP + MySQL WordPress setup
2. Copy the folder `ultimart-whatsapp-catalog` into your local WordPress `wp-content/plugins/` folder
3. Open local WordPress Admin
4. Activate `Ultimart WhatsApp Catalog`
5. Create the `Shop` and `Order` pages using the shortcodes from this README
6. Visit the pages in your browser and test:
   - product list page
   - direct product links
   - order form submission
   - admin order list

If you want to prove it is working correctly every time, test this checklist after each update:

1. Open the Shop page and confirm new text/design appears
2. Open one direct product URL like `/order/?ultimart_product=ultimart-combo`
3. Change quantity and confirm total price updates
4. Submit a test order
5. Check the order in `WordPress Admin > আল্টিমার্ট অর্ডার`

## What to edit for content and polish

Use these files for regular updates:

- `ultimart-whatsapp-catalog.php`
  - product names
  - prices
  - offer text
  - hero heading/subtitle
  - features
  - delivery text
- `assets/style.css`
  - typography
  - spacing
  - colors
  - card design
  - button styles
- `assets/app.js`
  - quantity behavior
  - order submit behavior
  - WhatsApp message text
- `assets/images/`
  - product images

## Best workflow for future updates

Do not edit directly on the live WordPress site if you plan to keep improving this plugin.

Use this workflow:

1. Keep this plugin folder as your source code
2. Make changes locally first
3. Test on a local WordPress site
4. Rebuild the ZIP
5. Upload the new ZIP to WordPress
6. Verify the live page

Important:

- CSS and JS caching was a common reason updates looked missing
- This plugin now uses file modification time for `style.css` and `app.js`
- That means when you change design or JS and upload again, WordPress should load the new asset automatically

## Rebuild ZIP after changes

From the project root, run this in PowerShell:

```powershell
if (Test-Path '.\ultimart-whatsapp-catalog.zip') { Remove-Item '.\ultimart-whatsapp-catalog.zip' }
Compress-Archive -Path '.\ultimart-whatsapp-catalog' -DestinationPath '.\ultimart-whatsapp-catalog.zip'
```

Then upload the new ZIP in WordPress plugin upload.

## If updates still do not appear

Check these in order:

1. Confirm you uploaded the new ZIP, not an old one
2. Confirm the plugin folder inside the ZIP is `ultimart-whatsapp-catalog`
3. Clear browser cache and hard refresh
4. Clear any cache plugin like LiteSpeed, WP Rocket, or server cache
5. Confirm your edited file was really inside the uploaded ZIP
6. If text changed in PHP but still does not update, check whether the page content itself in WordPress was edited manually
