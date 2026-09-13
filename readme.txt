=== BCV Exchange Rate Widget ===
Contributors: vainknight ft. claude code
Tags: bcv, usd, venezuela, exchange rate, elementor, gutenberg
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2

Displays live BCV USD exchange rate (Venezuela), resilient to page caching (LiteSpeed/Cloudflare).

== Description ==

This plugin fetches the official USD exchange rate published by the Central Bank of Venezuela (BCV) every 6 hours via a background cron job, exposing it on your site in three ways:

1. Shortcodes: [tasa_bcv_simple] and [tasa_bcv_fecha]
2. Native Gutenberg Block: "Tasa BCV" (Widgets category)
3. Elementor Widget: "Tasa BCV" (Tasa BCV category)

The rate refreshes dynamically in the browser via an uncached REST endpoint (bcv/v1/tasa), ensuring visitors always see the real-time value even if the HTML page is cached for days.

== Installation ==

1. Upload the `tasa-bcv-widget` folder to `/wp-content/plugins/`.
2. Activate it through the 'Plugins' menu in WordPress.
3. Use the shortcodes, drag the "Tasa BCV" block in the Gutenberg editor, or place the "Tasa BCV" widget in Elementor.

To troubleshoot connection issues with the BCV, visit any page on your site (while logged in as an administrator) and add `?tbw_debug=1` to the URL.

== Changelog ==

= 1.0.0 =
* Initial release: shortcodes, Gutenberg block, and Elementor widget.
