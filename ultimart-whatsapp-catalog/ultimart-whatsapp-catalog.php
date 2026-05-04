<?php
/**
 * Plugin Name: Ultimart WhatsApp Catalog
 * Description: Bangla product list and separate product detail/order page with database order storage.
 * Version: 3.4.4
 * Author: Hridoy
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Ultimart_WhatsApp_Catalog {
    const VERSION = '3.4.4';
    const TABLE_SUFFIX = 'ultimart_orders';

    public function __construct() {
        add_action('init', array($this, 'maybe_upgrade'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_shortcode('ultimart_products', array($this, 'render_product_list_shortcode'));
        add_shortcode('ultimart_product_list', array($this, 'render_product_list_shortcode'));
        add_shortcode('ultimart_product_detail', array($this, 'render_product_detail_shortcode'));
        add_shortcode('ultimart_campaign_video', array($this, 'render_campaign_video_shortcode'));
        add_action('wp_ajax_ultimart_place_order', array($this, 'handle_order_submission'));
        add_action('wp_ajax_nopriv_ultimart_place_order', array($this, 'handle_order_submission'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
    }

    public static function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_SUFFIX;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id varchar(120) NOT NULL,
            product_name varchar(255) NOT NULL,
            unit_price decimal(10,2) NOT NULL DEFAULT 0,
            quantity int(10) unsigned NOT NULL DEFAULT 1,
            total_price decimal(10,2) NOT NULL DEFAULT 0,
            customer_name varchar(160) NOT NULL,
            phone varchar(40) NOT NULL,
            area varchar(160) NOT NULL,
            address text NOT NULL,
            notes text NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            source_page varchar(255) NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option('ultimart_catalog_version', self::VERSION);
    }

    public function maybe_upgrade() {
        if (get_option('ultimart_catalog_version') !== self::VERSION) {
            self::activate();
        }
    }

    public function register_assets() {
        wp_register_style(
            'ultimart-whatsapp-catalog',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            array(),
            self::VERSION
        );

        wp_register_script(
            'ultimart-whatsapp-catalog',
            plugin_dir_url(__FILE__) . 'assets/app.js',
            array(),
            self::VERSION,
            true
        );
    }

    private function get_price($amount) {
        return number_format_i18n((float) $amount, 0);
    }

    private function get_discount_percent($price, $old_price) {
        $price = (float) $price;
        $old_price = (float) $old_price;

        if ($old_price <= 0 || $old_price <= $price) {
            return 0;
        }

        return (int) round((($old_price - $price) / $old_price) * 100);
    }

    private function get_savings_amount($price, $old_price) {
        $price = (float) $price;
        $old_price = (float) $old_price;

        if ($old_price <= $price) {
            return 0;
        }

        return $old_price - $price;
    }

    private function get_video_embed_url($video_url) {
        $video_url = trim((string) $video_url);

        if (empty($video_url)) {
            return '';
        }

        if (preg_match('~(?:youtube\.com/shorts/|youtu\.be/|youtube\.com/watch\?v=|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $video_url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=0&rel=0&modestbranding=1&playsinline=1';
        }

        return esc_url_raw($video_url);
    }

    private function get_orders_table_name() {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    private function build_placeholder_image($title, $accent_a, $accent_b) {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 900" role="img" aria-label="%1$s">
                <defs>
                    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
                        <stop offset="0%%" stop-color="%2$s" />
                        <stop offset="100%%" stop-color="%3$s" />
                    </linearGradient>
                </defs>
                <rect width="900" height="900" rx="52" fill="url(#g)" />
                <circle cx="715" cy="180" r="160" fill="rgba(255,255,255,0.10)" />
                <circle cx="180" cy="740" r="170" fill="rgba(255,255,255,0.10)" />
                <rect x="66" y="74" width="768" height="752" rx="36" fill="rgba(18,14,12,0.24)" stroke="rgba(255,255,255,0.24)" />
                <text x="92" y="184" fill="#ffffff" font-family="Georgia, Times New Roman, serif" font-size="40" letter-spacing="8">ULTIMART BD</text>
                <text x="92" y="352" fill="#ffffff" font-family="Georgia, Times New Roman, serif" font-size="78" font-weight="700">%1$s</text>
                <text x="92" y="432" fill="#f7ead8" font-family="Arial, sans-serif" font-size="28">একই নামে JPG ছবি দিলে এই প্রিভিউ বদলে যাবে।</text>
                <rect x="92" y="520" width="260" height="66" rx="33" fill="#ffffff" fill-opacity="0.16" />
                <text x="134" y="563" fill="#ffffff" font-family="Arial, sans-serif" font-size="28">প্রিমিয়াম প্রোডাক্ট</text>
            </svg>',
            esc_html($title),
            esc_attr($accent_a),
            esc_attr($accent_b)
        );

        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
    }

    private function resolve_product_image($product) {
        if (!empty($product['image_url'])) {
            return esc_url_raw($product['image_url']);
        }

        if (!empty($product['image_file'])) {
            $absolute_path = plugin_dir_path(__FILE__) . $product['image_file'];

            if (file_exists($absolute_path)) {
                return plugin_dir_url(__FILE__) . str_replace('\\', '/', $product['image_file']);
            }
        }

        return $this->build_placeholder_image($product['name'], $product['accent_a'], $product['accent_b']);
    }

    private function get_products() {
        $products = array(
            array(
                'id' => 'binbond-silver',
                'name' => 'BINBOND লাক্সারি ঘড়ি সিলভার হোয়াইট',
                'tag' => 'লাক্সারি ঘড়ি',
                'badge' => 'বেস্ট সেলার',
                'price' => 1490,
                'old_price' => 1990,
                'excerpt' => 'সিলভার-গোল্ড প্রিমিয়াম ফিনিশ, হোয়াইট ডায়াল এবং ডে-ডেট ডিসপ্লে।',
                'image_url' => '',
                'image_file' => 'assets/images/product-1.jpeg',
                'accent_a' => '#a88d62',
                'accent_b' => '#2d2b33',
                'description' => array(
                    'প্রিমিয়াম ডুয়াল-টোন চেইন এবং স্মার্ট কেস ডিজাইন এই ঘড়িটিকে ফরমাল ও পার্টি লুকের জন্য উপযুক্ত করে।',
                    'হোয়াইট ডায়াল, গোল্ড মার্কার এবং প্রিমিয়াম ফিনিশের কারণে এটি উপহার হিসেবেও দারুণ মানানসই।',
                ),
                'features' => array(
                    'হোয়াইট লাক্সারি ডায়াল',
                    'সিলভার ও গোল্ড ডুয়াল-টোন মেটাল চেইন',
                    'ডে-ডেট ডিসপ্লে',
                    'প্রিমিয়াম বক্সসহ',
                ),
                'delivery' => array(
                    'ক্যাশ অন ডেলিভারি',
                    'ঢাকার ভিতরে দ্রুত ডেলিভারি',
                    'অফিস, বিয়ে এবং ফরমাল লুকে মানানসই',
                ),
            ),
            array(
                'id' => 'binbond-blue',
                'name' => 'BINBOND লাক্সারি ঘড়ি রয়্যাল ব্লু',
                'tag' => 'লাক্সারি ঘড়ি',
                'badge' => 'নতুন এসেছে',
                'price' => 1490,
                'old_price' => 1990,
                'excerpt' => 'ডিপ ব্লু ডায়াল, গোল্ড অ্যাকসেন্ট এবং চোখে পড়ার মতো প্রিমিয়াম লুক।',
                'image_url' => '',
                'image_file' => 'assets/images/product-2.jpeg',
                'accent_a' => '#1f4581',
                'accent_b' => '#0f1017',
                'description' => array(
                    'রয়্যাল ব্লু ডায়াল আলোতে আরও উজ্জ্বল দেখায় এবং ঘড়িটিকে আরও প্রিমিয়াম করে তোলে।',
                    'যারা সিলভার ডায়ালের বাইরে একটু বেশি আকর্ষণীয় কিছু চান, তাদের জন্য এটি খুব ভালো নির্বাচন।',
                ),
                'features' => array(
                    'ডিপ ব্লু রিফ্লেক্টিভ ডায়াল',
                    'ডুয়াল-টোন মেটাল চেইন',
                    'ডে-ডেট ডিসপ্লে',
                    'রেডি-টু-গিফট বক্স',
                ),
                'delivery' => array(
                    'ক্যাশ অন ডেলিভারি',
                    'স্টাইলিশ গিফট অপশন',
                    'ক্যাজুয়াল ও ফরমাল দুই লুকেই মানায়',
                ),
            ),
            array(
                'id' => 'binbond-black',
                'name' => 'BINBOND লাক্সারি ঘড়ি মিডনাইট ব্ল্যাক',
                'tag' => 'লাক্সারি ঘড়ি',
                'badge' => 'এলিগ্যান্ট পিক',
                'price' => 1490,
                'old_price' => 1990,
                'excerpt' => 'ডার্ক ব্ল্যাক ডায়াল এবং প্রিমিয়াম মেটাল ব্রেসলেটের সিরিয়াস লাক্সারি লুক।',
                'image_url' => '',
                'image_file' => 'assets/images/product-3.jpeg',
                'accent_a' => '#3a302e',
                'accent_b' => '#12131a',
                'description' => array(
                    'এই ব্ল্যাক এডিশনটি আরও ডিপ, শক্তিশালী এবং পরিমিত লুক দেয়।',
                    'যারা হাতে একটু গাঢ় এবং শক্তিশালী স্টেটমেন্ট চান, তাদের জন্য এই মডেলটি চমৎকার।',
                ),
                'features' => array(
                    'মিডনাইট ব্ল্যাক ডায়াল',
                    'প্রিমিয়াম ডিটেইলিংসহ মেটাল ব্রেসলেট',
                    'ডে-ডেট ডিসপ্লে',
                    'ফরমাল-রেডি প্রেজেন্টেশন বক্স',
                ),
                'delivery' => array(
                    'ক্যাশ অন ডেলিভারি',
                    'দৈনন্দিন ব্যবহারেও মানানসই',
                    'ফরমাল ড্রেসের সাথে বিশেষভাবে ভালো মানায়',
                ),
            ),
            array(
                'id' => 'ultimart-combo',
                'name' => 'আল্টিমার্ট কম্বো প্যাক',
                'tag' => 'কম্বো অফার',
                'badge' => 'হট কম্বো',
                'price' => 1990,
                'old_price' => 2690,
                'excerpt' => 'সানগ্লাস, ওয়ালেট এবং বেজ-স্ট্র্যাপ ওয়াচ একসাথে একটি ভ্যালু কম্বো।',
                'image_url' => '',
                'image_file' => 'assets/images/product-4.jpeg',
                'accent_a' => '#6c4e2d',
                'accent_b' => '#151515',
                'description' => array(
                    'এই কম্বোতে রয়েছে স্টাইলিশ ওভাল সানগ্লাস, প্রিমিয়াম ওয়ালেট এবং স্পোর্টস ওয়াচ।',
                    'এক অর্ডারে পুরো এক্সেসরিজ সেট চাইলে বা উপহার দিতে চাইলে এই কম্বোটি সবচেয়ে ভালো।',
                ),
                'features' => array(
                    'ডার্ক লেন্সসহ ওভাল সানগ্লাস',
                    'স্টিচড ফিনিশসহ ওয়ালেট',
                    'বেজ স্ট্র্যাপ স্পোর্টস ওয়াচ',
                    'ভ্যালু কম্বো অফার',
                ),
                'delivery' => array(
                    'ক্যাশ অন ডেলিভারি',
                    'গিফট-রেডি সেট',
                    'ব্যক্তিগত ব্যবহার বা রিসেলের জন্য উপযুক্ত',
                ),
            ),
        );

        foreach ($products as &$product) {
            $product['image'] = $this->resolve_product_image($product);
        }

        unset($product);

        return $products;
    }

    private function get_products_index() {
        $indexed = array();

        foreach ($this->get_products() as $product) {
            $indexed[$product['id']] = $product;
        }

        return $indexed;
    }

    private function get_product($product_id) {
        $products = $this->get_products_index();

        return isset($products[$product_id]) ? $products[$product_id] : null;
    }

    private function build_whatsapp_url($phone, $order_data) {
        if (empty($phone)) {
            return '';
        }

        $lines = array(
            'আসসালামু আলাইকুম, একটি নতুন অর্ডার এসেছে।',
            '',
            'অর্ডার আইডি: #' . $order_data['order_id'],
            'পণ্যের নাম: ' . $order_data['product_name'],
            'পরিমাণ: ' . $order_data['quantity'],
            'কাস্টমারের নাম: ' . $order_data['customer_name'],
            'ফোন নম্বর: ' . $order_data['phone'],
            'এলাকা: ' . $order_data['area'],
            'ঠিকানা: ' . $order_data['address'],
        );

        if (!empty($order_data['notes'])) {
            $lines[] = 'নোট: ' . $order_data['notes'];
        }

        return 'https://wa.me/' . preg_replace('/\D+/', '', $phone) . '?text=' . rawurlencode(implode("\n", $lines));
    }

    private function get_detail_page_url($atts) {
        $detail_page = !empty($atts['detail_page']) ? trim($atts['detail_page']) : '';

        if (empty($detail_page)) {
            return get_permalink();
        }

        if (false !== strpos($detail_page, 'http://') || false !== strpos($detail_page, 'https://')) {
            return $detail_page;
        }

        return home_url($detail_page);
    }

    public function render_product_list_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'title' => 'কেন আমাদের BIN-BOND WATCH সবার থেকে সেরা?',
                'subtitle' => 'বিশাল ছাড়, ক্যাশ অন ডেলিভারি, দ্রুত কনফার্মেশন এবং বিশ্বস্ত সাপোর্টের জন্য আজই আপনার পছন্দের মডেলটি অর্ডার করুন।',
                'detail_page' => '',
                'button_text' => 'অর্ডার করুন',
            ),
            $atts,
            'ultimart_product_list'
        );

        $products = $this->get_products();
        $detail_page_url = $this->get_detail_page_url($atts);

        wp_enqueue_style('ultimart-whatsapp-catalog');
        wp_enqueue_script('ultimart-whatsapp-catalog');

        ob_start();
        ?>
        <section class="ultimart-list-page">
            <div class="ultimart-list-hero">
                <div class="ultimart-list-hero__copy">
                    <span class="ultimart-section-eyebrow">Ultimart BD</span>
                    <h2><?php echo esc_html($atts['title']); ?></h2>
                    <p><?php echo esc_html($atts['subtitle']); ?></p>

                    <div class="ultimart-list-hero__metrics">
                        <div class="ultimart-metric-card">
                            <strong>4</strong>
                            <span>ডিরেক্ট অর্ডার প্রোডাক্ট</span>
                        </div>
                        <div class="ultimart-metric-card">
                            <strong>COD</strong>
                            <span>ক্যাশ অন ডেলিভারি</span>
                        </div>
                        <div class="ultimart-metric-card">
                            <strong>24/7</strong>
                            <span>হোয়াটসঅ্যাপ সাপোর্ট</span>
                        </div>
                    </div>
                </div>

                <aside class="ultimart-list-hero__offer">
                    <span class="ultimart-list-hero__offer-label">কেন আমাদের কাছে অর্ডার করবেন?</span>
                    <h3>কারণ আমরা দিচ্ছি বিশাল ছাড়, দ্রুত ডেলিভারি এবং অর্ডারের পর নির্ভরযোগ্য সাপোর্ট।</h3>
                    <ul class="ultimart-inline-points">
                        <li>বাজারের তুলনায় সেরা অফার প্রাইস</li>
                        <li>পণ্য হাতে পেয়ে টাকা দেওয়ার সুবিধা</li>
                        <li>অর্ডারের পর দ্রুত ফোন/হোয়াটসঅ্যাপ কনফার্মেশন</li>
                        <li>কাস্টমার সাপোর্ট ও বিশ্বাসযোগ্য সার্ভিস</li>
                    </ul>
                </aside>
            </div>

            <div class="ultimart-trust-strip">
                <div class="ultimart-trust-pill">ক্যাশ অন ডেলিভারি</div>
                <div class="ultimart-trust-pill">দ্রুত কনফার্ম</div>
                <div class="ultimart-trust-pill">গিফট রেডি ফিনিশ</div>
            </div>

            <div class="ultimart-product-grid" data-product-carousel>
                <?php foreach ($products as $product_index => $product) : ?>
                    <?php
                    $product_url = add_query_arg('ultimart_product', $product['id'], $detail_page_url);
                    $discount_percent = $this->get_discount_percent($product['price'], $product['old_price']);
                    $savings_amount = $this->get_savings_amount($product['price'], $product['old_price']);
                    ?>
                    <article class="ultimart-product-card">
                        <a
                            class="ultimart-product-card__link"
                            href="<?php echo esc_url($product_url); ?>"
                            aria-label="<?php echo esc_attr($product['name'] . ' - ' . $atts['button_text']); ?>"
                        >
                            <div class="ultimart-product-card__media">
                                <img
                                    src="<?php echo esc_url($product['image']); ?>"
                                    alt="<?php echo esc_attr($product['name']); ?>"
                                    loading="lazy"
                                />
                                <span class="ultimart-product-card__badge"><?php echo esc_html($product['badge']); ?></span>
                            </div>

                            <div class="ultimart-product-card__body">
                                <div class="ultimart-product-card__header">
                                    <span class="ultimart-product-card__tag"><?php echo esc_html($product['tag']); ?></span>
                                    <?php if ($discount_percent > 0) : ?>
                                        <span class="ultimart-product-card__save"><?php echo esc_html($discount_percent); ?>% সাশ্রয়</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo esc_html($product['name']); ?></h3>
                                <p><?php echo esc_html($product['excerpt']); ?></p>
                                <div class="ultimart-product-card__price">
                                    <strong><?php echo esc_html($this->get_price($product['price'])); ?> &#2547;</strong>
                                    <span><?php echo esc_html($this->get_price($product['old_price'])); ?> &#2547;</span>
                                </div>
                                <div class="ultimart-product-card__meta">
                                    <?php if ($savings_amount > 0) : ?>
                                        <span>আপনার সাশ্রয় <?php echo esc_html($this->get_price($savings_amount)); ?> &#2547;</span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html($product['delivery'][0]); ?></span>
                                </div>
                                <span class="ultimart-product-card__cta"><?php echo esc_html($atts['button_text']); ?></span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="ultimart-carousel-dots" data-carousel-dots aria-label="প্রোডাক্ট স্লাইড">
                <?php foreach ($products as $product_index => $product) : ?>
                    <button
                        type="button"
                        class="ultimart-carousel-dot<?php echo 0 === $product_index ? ' is-active' : ''; ?>"
                        data-carousel-dot="<?php echo esc_attr($product_index); ?>"
                        aria-label="<?php echo esc_attr(($product_index + 1) . ' নম্বর প্রোডাক্ট দেখুন'); ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function render_campaign_video_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'video_url' => 'https://youtube.com/shorts/ugawiWnehts?feature=share',
            ),
            $atts,
            'ultimart_campaign_video'
        );

        $video_embed_url = $this->get_video_embed_url($atts['video_url']);

        if (empty($video_embed_url)) {
            return '';
        }

        wp_enqueue_style('ultimart-whatsapp-catalog');

        ob_start();
        ?>
        <section class="ultimart-home-video">
            <div class="ultimart-campaign-video">
                <iframe
                    src="<?php echo esc_url($video_embed_url); ?>"
                    title="BIN-BOND WATCH ভিডিও"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                ></iframe>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function render_product_detail_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'whatsapp' => '8801000000000',
                'back_page' => '',
                'back_text' => 'সব প্রোডাক্ট দেখুন',
            ),
            $atts,
            'ultimart_product_detail'
        );

        $product_id = isset($_GET['ultimart_product']) ? sanitize_key(wp_unslash($_GET['ultimart_product'])) : '';
        $product = $this->get_product($product_id);

        wp_enqueue_style('ultimart-whatsapp-catalog');
        wp_enqueue_script('ultimart-whatsapp-catalog');

        $back_url = !empty($atts['back_page']) ? home_url($atts['back_page']) : wp_get_referer();

        if (!$product) {
            ob_start();
            ?>
            <section class="ultimart-empty-state">
                <h2>এখনও কোনো প্রোডাক্ট সিলেক্ট করা হয়নি</h2>
                <p>পছন্দের প্রোডাক্টটি বেছে নিন, তারপর দ্রুত অর্ডার সম্পন্ন করুন।</p>
                <?php if (!empty($back_url)) : ?>
                    <a class="ultimart-back-link" href="<?php echo esc_url($back_url); ?>">
                        <?php echo esc_html($atts['back_text']); ?>
                    </a>
                <?php endif; ?>
            </section>
            <?php

            return ob_get_clean();
        }

        $payload = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ultimart_order_nonce'),
            'whatsapp' => preg_replace('/\D+/', '', $atts['whatsapp']),
            'currencySymbol' => html_entity_decode('&#2547;', ENT_QUOTES, 'UTF-8'),
            'product' => $product,
        );
        $discount_percent = $this->get_discount_percent($product['price'], $product['old_price']);
        $savings_amount = $this->get_savings_amount($product['price'], $product['old_price']);

        ob_start();
        ?>
        <section class="ultimart-detail-page" data-product-detail="<?php echo esc_attr(wp_json_encode($payload)); ?>">
            <?php if (!empty($back_url)) : ?>
                <a class="ultimart-back-link" href="<?php echo esc_url($back_url); ?>">
                    <?php echo esc_html($atts['back_text']); ?>
                </a>
            <?php endif; ?>

            <div class="ultimart-trust-strip ultimart-trust-strip--detail">
                <div class="ultimart-trust-pill">প্রিমিয়াম ঘড়ির ডিটেইল</div>
                <div class="ultimart-trust-pill">মোবাইলে দ্রুত অর্ডার</div>
                <div class="ultimart-trust-pill">হোয়াটসঅ্যাপ সাপোর্ট</div>
            </div>

            <div class="ultimart-detail-shell">
                <div class="ultimart-detail-media-card">
                    <div class="ultimart-detail-media">
                        <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>" />
                    </div>

                    <div class="ultimart-media-highlights">
                        <?php foreach (array_slice($product['delivery'], 0, 3) as $delivery_point) : ?>
                            <div class="ultimart-media-highlights__item"><?php echo esc_html($delivery_point); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ultimart-detail-info-card">
                    <div class="ultimart-detail-info__top">
                        <div class="ultimart-detail-topline">
                            <span class="ultimart-product-card__tag"><?php echo esc_html($product['tag']); ?></span>
                            <span class="ultimart-inline-badge"><?php echo esc_html($product['badge']); ?></span>
                        </div>
                        <h2><?php echo esc_html($product['name']); ?></h2>
                        <p><?php echo esc_html($product['excerpt']); ?></p>
                    </div>

                    <div class="ultimart-detail-price-panel">
                        <div class="ultimart-product-card__price ultimart-detail-price">
                            <strong><?php echo esc_html($this->get_price($product['price'])); ?> &#2547;</strong>
                            <span><?php echo esc_html($this->get_price($product['old_price'])); ?> &#2547;</span>
                        </div>
                        <?php if ($discount_percent > 0) : ?>
                            <div class="ultimart-detail-price-panel__badge"><?php echo esc_html($discount_percent); ?>% সাশ্রয়</div>
                        <?php endif; ?>
                    </div>

                    <div class="ultimart-detail-points">
                        <div class="ultimart-detail-point">
                            <strong><?php echo esc_html($this->get_price($savings_amount)); ?> &#2547;</strong>
                            <span>তাৎক্ষণিক সাশ্রয়</span>
                        </div>
                        <div class="ultimart-detail-point">
                            <strong>1 page</strong>
                            <span>দ্রুত অর্ডার ফ্লো</span>
                        </div>
                        <div class="ultimart-detail-point">
                            <strong>WhatsApp</strong>
                            <span>ফলোআপ সাপোর্ট</span>
                        </div>
                    </div>
                    <div class="ultimart-detail-actions">
                        <a class="ultimart-detail-actions__primary" href="#ultimart-order-form">
                            অর্ডার করুন
                        </a>
                    </div>

                    <div class="ultimart-detail-columns">
                        <div class="ultimart-detail-block">
                            <h3>পণ্যের বিবরণ</h3>
                            <?php foreach ($product['description'] as $line) : ?>
                                <p><?php echo esc_html($line); ?></p>
                            <?php endforeach; ?>
                        </div>

                        <div class="ultimart-detail-block">
                            <h3>মূল বৈশিষ্ট্য</h3>
                            <ul>
                                <?php foreach ($product['features'] as $item) : ?>
                                    <li><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ultimart-order-shell">
                <div class="ultimart-order-summary">
                    <span class="ultimart-section-eyebrow">দ্রুত অর্ডার</span>
                    <h3><?php echo esc_html($product['name']); ?></h3>
                    <p>আজই অর্ডার করুন। সীমিত স্টক, দ্রুত কনফার্মেশনের জন্য নিচের তথ্য পূরণ করুন।</p>

                    <div class="ultimart-summary-cards">
                        <div class="ultimart-summary-card">
                            <span class="ultimart-summary-card__label">অফার</span>
                            <strong><?php echo esc_html($discount_percent); ?>% ছাড়</strong>
                        </div>
                        <div class="ultimart-summary-card">
                            <span class="ultimart-summary-card__label">সাপোর্ট</span>
                            <strong>হোয়াটসঅ্যাপ রেডি</strong>
                        </div>
                    </div>

                    <div class="ultimart-order-summary__row">
                        <span>একক দাম</span>
                        <strong data-summary="unit-price"><?php echo esc_html($this->get_price($product['price'])); ?> &#2547;</strong>
                    </div>

                    <div class="ultimart-order-summary__row ultimart-order-summary__qty">
                        <span>পরিমাণ</span>
                        <div class="ultimart-qty">
                            <button type="button" class="ultimart-qty__btn" data-action="decrease">-</button>
                            <input type="text" value="1" inputmode="numeric" readonly data-quantity />
                            <button type="button" class="ultimart-qty__btn" data-action="increase">+</button>
                        </div>
                    </div>

                    <div class="ultimart-order-summary__row ultimart-order-summary__total">
                        <span>মোট</span>
                        <strong data-summary="total"><?php echo esc_html($this->get_price($product['price'])); ?> &#2547;</strong>
                    </div>
                </div>

                <form id="ultimart-order-form" class="ultimart-order-form" data-order-form>
                    <input type="hidden" name="action" value="ultimart_place_order" />
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('ultimart_order_nonce')); ?>" />
                    <input type="hidden" name="product_id" value="<?php echo esc_attr($product['id']); ?>" data-input="product_id" />
                    <input type="hidden" name="quantity" value="1" data-input="quantity" />
                    <input type="hidden" name="source_page" value="<?php echo esc_attr(get_the_title()); ?>" />

                    <div class="ultimart-order-form__head">
                        <span class="ultimart-section-eyebrow">ডেলিভারি তথ্য</span>
                        <h3>অর্ডার সম্পন্ন করুন</h3>
                        <p>সঠিক নাম, ফোন নাম্বার ও ঠিকানা দিন। আমরা দ্রুত আপনার অর্ডার কনফার্ম করব।</p>
                    </div>

                    <div class="ultimart-form-note">
                        <strong>দ্রুত অর্ডার ফর্ম</strong>
                        <span>একবার তথ্য দিলেই অর্ডার WordPress-এ সেভ হবে এবং চাইলে হোয়াটসঅ্যাপে পাঠানো যাবে।</span>
                    </div>

                    <div class="ultimart-order-form__grid">
                        <label class="ultimart-field">
                            <span>আপনার নাম</span>
                            <input type="text" name="customer_name" placeholder="আপনার পূর্ণ নাম" required />
                        </label>

                        <label class="ultimart-field">
                            <span>ফোন নম্বর</span>
                            <input type="tel" name="phone" placeholder="01XXXXXXXXX" required />
                        </label>
                    </div>

                    <div class="ultimart-order-form__grid">
                        <label class="ultimart-field">
                            <span>ডেলিভারি এলাকা</span>
                            <input type="text" name="area" placeholder="ঢাকা / চট্টগ্রাম / ইত্যাদি" required />
                        </label>

                        <label class="ultimart-field">
                            <span>ল্যান্ডমার্ক / বিশেষ নির্দেশনা</span>
                            <input type="text" name="notes" placeholder="ঐচ্ছিক" />
                        </label>
                    </div>

                    <label class="ultimart-field">
                        <span>সম্পূর্ণ ঠিকানা</span>
                        <textarea name="address" rows="4" placeholder="বাসা, রোড, থানা, জেলা" required></textarea>
                    </label>

                    <div class="ultimart-order-form__actions">
                        <button type="submit" class="ultimart-order-form__submit">অর্ডার করুন</button>
                        <a href="#" class="ultimart-order-form__whatsapp" target="_blank" rel="noopener" data-whatsapp-link>
                            WhatsApp এ পাঠান
                        </a>
                    </div>

                    <p class="ultimart-order-form__message" data-form-message></p>
                </form>
            </div>

            <div class="ultimart-mobile-order-bar">
                <div class="ultimart-mobile-order-bar__price">
                    <span>অর্ডার শুরু</span>
                    <strong><?php echo esc_html($this->get_price($product['price'])); ?> &#2547;</strong>
                </div>
                <a class="ultimart-mobile-order-bar__cta" href="#ultimart-order-form">অর্ডার করুন</a>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function handle_order_submission() {
        check_ajax_referer('ultimart_order_nonce', 'nonce');

        $products = $this->get_products_index();
        $product_id = isset($_POST['product_id']) ? sanitize_key(wp_unslash($_POST['product_id'])) : '';
        $quantity = isset($_POST['quantity']) ? absint(wp_unslash($_POST['quantity'])) : 1;
        $customer_name = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $area = isset($_POST['area']) ? sanitize_text_field(wp_unslash($_POST['area'])) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field(wp_unslash($_POST['address'])) : '';
        $notes = isset($_POST['notes']) ? sanitize_text_field(wp_unslash($_POST['notes'])) : '';
        $source_page = isset($_POST['source_page']) ? sanitize_text_field(wp_unslash($_POST['source_page'])) : '';

        if (empty($product_id) || !isset($products[$product_id])) {
            wp_send_json_error(array('message' => 'একটি সঠিক পণ্য নির্বাচন করুন।'), 400);
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        if (empty($customer_name) || empty($phone) || empty($area) || empty($address)) {
            wp_send_json_error(array('message' => 'সব প্রয়োজনীয় কাস্টমার ও শিপিং তথ্য পূরণ করুন।'), 400);
        }

        $product = $products[$product_id];
        $unit_price = (float) $product['price'];
        $total_price = $unit_price * $quantity;

        global $wpdb;

        $inserted = $wpdb->insert(
            $this->get_orders_table_name(),
            array(
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'unit_price' => $unit_price,
                'quantity' => $quantity,
                'total_price' => $total_price,
                'customer_name' => $customer_name,
                'phone' => $phone,
                'area' => $area,
                'address' => $address,
                'notes' => $notes,
                'status' => 'pending',
                'source_page' => $source_page,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            wp_send_json_error(array('message' => 'অর্ডার সম্পন্ন করা যায়নি। আবার চেষ্টা করুন।'), 500);
        }

        $order_id = (int) $wpdb->insert_id;
        $whatsapp_number = isset($_POST['whatsapp']) ? sanitize_text_field(wp_unslash($_POST['whatsapp'])) : '';

        wp_send_json_success(
            array(
                'message' => 'অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে।',
                'order_id' => $order_id,
                'whatsapp_url' => $this->build_whatsapp_url(
                    $whatsapp_number,
                    array(
                        'order_id' => $order_id,
                        'product_name' => $product['name'],
                        'quantity' => $quantity,
                        'customer_name' => $customer_name,
                        'phone' => $phone,
                        'area' => $area,
                        'address' => $address,
                        'notes' => $notes,
                    )
                ),
            )
        );
    }

    public function register_admin_menu() {
        add_menu_page(
            'আল্টিমার্ট অর্ডারসমূহ',
            'আল্টিমার্ট অর্ডার',
            'manage_options',
            'ultimart-orders',
            array($this, 'render_orders_page'),
            'dashicons-cart',
            26
        );
    }

    public function render_orders_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        $orders = $wpdb->get_results(
            "SELECT * FROM {$this->get_orders_table_name()} ORDER BY created_at DESC, id DESC LIMIT 200"
        );
        ?>
        <div class="wrap">
            <h1>আল্টিমার্ট অর্ডারসমূহ</h1>
            <p>প্রোডাক্ট ডিটেইল পেজ থেকে আসা অর্ডারগুলো এখানে দেখা যাবে।</p>

            <?php if (empty($orders)) : ?>
                <p><strong>এখনও কোনো অর্ডার আসেনি।</strong></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>তারিখ</th>
                            <th>পণ্য</th>
                            <th>পরিমাণ</th>
                            <th>কাস্টমার</th>
                            <th>ফোন</th>
                            <th>এলাকা</th>
                            <th>ঠিকানা</th>
                            <th>মোট</th>
                            <th>স্ট্যাটাস</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order) : ?>
                            <tr>
                                <td>#<?php echo esc_html($order->id); ?></td>
                                <td><?php echo esc_html($order->created_at); ?></td>
                                <td>
                                    <strong><?php echo esc_html($order->product_name); ?></strong>
                                    <?php if (!empty($order->notes)) : ?>
                                        <br />
                                        <small>নোট: <?php echo esc_html($order->notes); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($order->quantity); ?></td>
                                <td><?php echo esc_html($order->customer_name); ?></td>
                                <td><?php echo esc_html($order->phone); ?></td>
                                <td><?php echo esc_html($order->area); ?></td>
                                <td><?php echo esc_html($order->address); ?></td>
                                <td><?php echo esc_html($this->get_price($order->total_price)); ?> &#2547;</td>
                                <td><?php echo 'pending' === $order->status ? 'অপেক্ষমাণ' : esc_html($order->status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, array('Ultimart_WhatsApp_Catalog', 'activate'));
new Ultimart_WhatsApp_Catalog();
