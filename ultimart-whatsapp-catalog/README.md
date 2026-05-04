# Ultimart WhatsApp Catalog

This version uses the structure below:

- Page 1: product list
- Page 2: single product detail + quantity + shipping form
- Same detail page can serve 4 separate direct product URLs
- Orders save in the WordPress database
- Admin order screen: `WordPress Admin > আল্টিমার্ট অর্ডার`

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
- Design and mobile carousel styles are in `assets/style.css`
- Carousel autoplay is in `assets/app.js`
