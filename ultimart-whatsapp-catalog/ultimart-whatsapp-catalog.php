<?php
/**
 * Plugin Name: Ultimart WhatsApp Catalog
 * Description: Product catalog shortcode with quantity selector and WhatsApp order button.
 * Version: 1.0.0
 * Author: Codex
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Ultimart_WhatsApp_Catalog {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_shortcode('ultimart_products', array($this, 'render_shortcode'));
    }

    public function register_assets() {
        wp_register_style(
            'ultimart-whatsapp-catalog',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            array(),
            '1.0.0'
        );

        wp_register_script(
            'ultimart-whatsapp-catalog',
            plugin_dir_url(__FILE__) . 'assets/app.js',
            array(),
            '1.0.0',
            true
        );
    }

    private function get_products() {
        return array(
            array(
                'id' => 'curren-watch',
                'name' => 'CURREN স্পোর্টস লাক্সারি মেনস ওয়াচ',
                'tag' => 'Watch',
                'excerpt' => 'ব্ল্যাক ডায়াল, বেজ লেদার স্ট্র্যাপ এবং প্রিমিয়াম স্পোর্টি লুক।',
                'description' => array(
                    'আপনার ক্যাজুয়াল এবং ফর্মাল লুককে আরও আকর্ষণীয় করে তুলতে CURREN ব্র্যান্ডের এই স্টাইলিশ ঘড়িটি হতে পারে আপনার সেরা পছন্দ।',
                    'আধুনিক ডিজাইন এবং টেকসই বিল্ড কোয়ালিটির সমন্বয়ে এটি তরুণ প্রজন্মের কাছে অত্যন্ত জনপ্রিয়।',
                ),
                'features' => array(
                    'ব্র্যান্ড: CURREN (কারেন)',
                    'স্ট্র্যাপ: বেজ / ট্যান লেদার',
                    'ডায়াল: ব্ল্যাক স্পোর্টি থিম',
                    'কেস: মজবুত মেটাল অ্যালয়',
                    'গ্লাস: স্ক্র্যাচ রেজিস্ট্যান্ট',
                ),
                'why_buy' => array(
                    'স্টাইলিশ, স্পোর্টি এবং গর্জিয়াস লুক',
                    'কম বাজেটে প্রিমিয়াম ঘড়ির অভিজ্ঞতা',
                ),
            ),
            array(
                'id' => 'armani-wallet',
                'name' => 'Emporio Armani লোগো লেদার মানিব্যাগ',
                'tag' => 'Wallet',
                'excerpt' => 'ব্ল্যাক লেদার, অরেঞ্জ স্টিচিং, ক্লাসি এবং টেকসই ডিজাইন।',
                'description' => array(
                    'আকর্ষণীয় ডিজাইনের এই প্রিমিয়াম লেদার মানিব্যাগটি অফিসিয়াল বা ক্যাজুয়াল দুই ধরনের ব্যবহারেই মানানসই।',
                    'জেনুইন লেদার, প্রিমিয়াম ফিনিশ এবং দৈনন্দিন ব্যবহারের জন্য আরামদায়ক সাইজ।',
                ),
                'features' => array(
                    'মেটেরিয়াল: জেনুইন লেদার',
                    'ডিজাইন: ব্ল্যাক বডি, অরেঞ্জ ডাবল স্টিচিং',
                    'লোগো: আর্মানি ঈগল আইকন',
                    'কম্পার্টমেন্ট: টাকা, কার্ড, আইডির জন্য পর্যাপ্ত স্লট',
                    'সাইজ: স্ট্যান্ডার্ড পকেট সাইজ',
                ),
                'why_buy' => array(
                    'ক্লাসি এবং টেকসই',
                    'অফিসিয়াল ও ক্যাজুয়াল দুই লুকেই মানায়',
                ),
            ),
            array(
                'id' => 'oval-sunglasses',
                'name' => 'স্টাইলিশ মেটাল ফ্রেম ওভাল সানগ্লাস',
                'tag' => 'Sunglasses',
                'excerpt' => 'ট্রেন্ডি ওভাল শেপ, ডার্ক লেন্স এবং হালকা মেটাল ফ্রেম।',
                'description' => array(
                    'আপনার লুকে একটি ক্লাসিক এবং প্রিমিয়াম টাচ যোগ করতে এই সানগ্লাসটি চমৎকার একটি পছন্দ।',
                    'আধুনিক ডিজাইন, মজবুত বিল্ড কোয়ালিটি এবং রোদে আরামদায়ক ব্যবহারের জন্য তৈরি।',
                ),
                'features' => array(
                    'ডিজাইন: ট্রেন্ডি ওভাল শেপ',
                    'ফ্রেম: উন্নত মানের মেটাল',
                    'লেন্স: ডার্ক লেন্স',
                    'কন্ডিশন: একদম নতুনের মতো',
                    'স্টাইল: ক্যাজুয়াল ও ফরমাল দুই ক্ষেত্রেই মানানসই',
                ),
                'why_buy' => array(
                    'প্রিমিয়াম লুক',
                    'হালকা এবং দীর্ঘস্থায়ী ফ্রেম',
                ),
            ),
            array(
                'id' => 'binbond-watch',
                'name' => 'BINBOND Luxury Watch',
                'tag' => 'Watch',
                'excerpt' => 'ডিপ ব্লু ডায়াল, গোল্ডেন রোমান নম্বর এবং ডুয়াল টোন চেইন।',
                'description' => array(
                    'আপনার ব্যক্তিত্বে রাজকীয় আভিজাত্য ফুটিয়ে তুলতে BINBOND ব্র্যান্ডের এই লাক্সারি ঘড়িটি দারুণ একটি পছন্দ।',
                    'যারা স্টাইল এবং কোয়ালিটির সমন্বয় পছন্দ করেন, এটি তাদের জন্য উপযুক্ত।',
                ),
                'features' => array(
                    'ডায়াল: Deep Blue, Black & White',
                    'ডিজাইন: গোল্ডেন রোমান সংখ্যা',
                    'গ্লাস: ডায়মন্ড কাট রিফ্লেক্টিভ গ্লাস',
                    'ডিসপ্লে: Day-Date সুবিধা',
                    'চেইন: সিলভার ও গোল্ডেন ডুয়াল টোন',
                    'মুভমেন্ট: Quartz',
                ),
                'why_buy' => array(
                    'অফিস, বিয়ে এবং ফরমাল ইভেন্টে মানানসই',
                    'লাক্সারি ফিনিশ এবং প্রিমিয়াম উপস্থিতি',
                ),
            ),
        );
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'whatsapp' => '8801000000000',
                'title' => 'আমাদের পণ্যসমূহ',
                'subtitle' => 'পণ্যে ক্লিক করুন, পরিমাণ ঠিক করুন, তারপর WhatsApp-এ অর্ডার পাঠান।',
            ),
            $atts,
            'ultimart_products'
        );

        $products = $this->get_products();

        wp_enqueue_style('ultimart-whatsapp-catalog');
        wp_enqueue_script('ultimart-whatsapp-catalog');

        $payload = array(
            'phone' => preg_replace('/\D+/', '', $atts['whatsapp']),
            'products' => $products,
            'siteName' => get_bloginfo('name'),
            'pageTitle' => get_the_title(),
        );

        ob_start();
        ?>
        <div
            class="ultimart-catalog"
            data-catalog="<?php echo esc_attr(wp_json_encode($payload)); ?>"
        >
            <div class="ultimart-catalog__hero">
                <p class="ultimart-catalog__eyebrow">WhatsApp Order Catalog</p>
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
            </div>

            <div class="ultimart-catalog__layout">
                <div class="ultimart-catalog__grid">
                    <?php foreach ($products as $index => $product) : ?>
                        <button
                            class="ultimart-card<?php echo 0 === $index ? ' is-active' : ''; ?>"
                            type="button"
                            data-product-id="<?php echo esc_attr($product['id']); ?>"
                        >
                            <span class="ultimart-card__tag"><?php echo esc_html($product['tag']); ?></span>
                            <strong class="ultimart-card__title"><?php echo esc_html($product['name']); ?></strong>
                            <span class="ultimart-card__excerpt"><?php echo esc_html($product['excerpt']); ?></span>
                            <span class="ultimart-card__action">Details + Order</span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="ultimart-detail" aria-live="polite">
                    <div class="ultimart-detail__header">
                        <span class="ultimart-detail__tag" data-field="tag"></span>
                        <h3 data-field="name"></h3>
                        <p data-field="excerpt"></p>
                    </div>

                    <div class="ultimart-detail__section">
                        <h4>বিস্তারিত</h4>
                        <div data-field="description"></div>
                    </div>

                    <div class="ultimart-detail__section">
                        <h4>মূল বৈশিষ্ট্য</h4>
                        <ul data-field="features"></ul>
                    </div>

                    <div class="ultimart-detail__section">
                        <h4>কেন কিনবেন</h4>
                        <ul data-field="why_buy"></ul>
                    </div>

                    <div class="ultimart-order">
                        <div class="ultimart-qty">
                            <span class="ultimart-qty__label">Quantity</span>
                            <div class="ultimart-qty__controls">
                                <button type="button" class="ultimart-qty__btn" data-action="decrease">-</button>
                                <input type="text" value="1" inputmode="numeric" readonly data-quantity />
                                <button type="button" class="ultimart-qty__btn" data-action="increase">+</button>
                            </div>
                        </div>

                        <a class="ultimart-order__button" href="#" target="_blank" rel="noopener" data-order-link>
                            WhatsApp-এ অর্ডার পাঠান
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }
}

new Ultimart_WhatsApp_Catalog();
