=== Advanced Dynamic Pricing for WooCommerce ===
Contributors: algolplus
Donate link: https://paypal.me/ipprokaev/0usd
Tags: woocommerce, dynamic pricing, discount, pricing rule, bulk discount
Requires PHP: 7.0
Requires at least: 4.8
Tested up to: 6.5
Stable tag: 4.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

All discount types - flexible and feature rich plugin.

== Description ==

This plugin helps you  quickly set discounts and pricing rules for your WooCommerce store.

Set up any kind of discount or dynamic pricing you like, and activate/deactivate rules as needed.

Configure fixed dollar amount adjustments, percentage adjustments, or set fixed price for the product or group of products.

Also supports role-based prices & bulk pricing. **Bulk tables can be designed with Customizer.** You should setup bulk rule for category/product at first and enable "Show Bulk Table" at tab "Settings".

= Some Examples  =

* Category-level discounts - discount products and provide free shipping
* Buy 4(or more) items on Friday and get 20% off
* Buy product X and get product Y for free - immediately added and visible in cart
* Buy a package -  discount it (each item separately), and also get a free product
* Apply bulk discount for selected items, available only to wholesale buyers
* Give a 10% discount to all "Accessories"(Category) if a product X is present in the cart

Check more examples [on our website](https://docs.algolplus.com/algol_pricing/sample-discount/).

= One pricing rule can  =
* Filter cart items by products, categories, tags or custom fields
* Modify price for each product separately
* Or set total price for whole set
* Apply cart discounts and fees
* Add free products on fly
* Use tables to get bulk rates
* Validate conditions for cart items, user roles or dates
* Track limits (only "max usage" supported currently)

= Interface settings =
* Show/hide original prices
* Show/hide badge "On Sale"
* Show/hide bulk discount table on the product page
* Set rule for  products which already on sale
* Add shortcodes to display discounted or BOGO products at separate pages
* and much more ...

[Pro version](https://algolplus.com/plugins/downloads/advanced-dynamic-pricing-woocommerce-pro/) can [adjust product price onfly](https://docs.algolplus.com/algol_pricing/advanced-features-in-action/), adds **exclusive rules, extra conditions, a lot of settings, and statistics** (which rules really work, which products are involved and how much does it cost for you).

Have an idea or feature request?
Please create a topic in the "Support" section with any ideas or suggestions for new features.

== Installation ==

= Automatic Installation =
Go to Wordpress dashboard, click  Plugins / Add New  , type 'Advanced Dynamic Pricing for WooCommerce' and hit Enter.
Install and activate plugin, visit WooCommerce > Pricing Rules.

= Manual Installation =
[Please, visit the link and follow the instructions](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Frequently Asked Questions ==

= How can I increase prices in my shop? =
You should setup negative discount.
= The plugin slows down my site a lot. Sometimes the cart page just freezes. =
It seems your websitе calls external API to do shipping calculations.
Please, visit >WooCommerce>Pricing Rules>Settings>Calculation, mark "Disable shipping calculation" and check speed.
= Free product can't be added to the cart. I see message "Sorry, this product cannot be purchased." =
WooCommerce verifies product before adding to the cart. So this product must be published, in stock and has price defined.
= How can I hide original prices? =
It's a PRO feature. You should turn off option "Show striked prices" at tab Settings, for category and product pages.
= I don't see "For sale" badge for variable products =
It's a PRO feature. You should turn on option "Calculate 'On Sale' badge for variable products" at tab Settings, section Calculations.
= Is it compatible with WPML? WOOCS ? =
Yes.
= Compatibility with my theme/plugin =
Free and pro versions use same core, so you can test it using free version. [Please, visit the link to see detailed reply](https://docs.algolplus.com/algol_pricing/common-faq/)
= How to allow customer to select free product =
You should create package rule and set zero price for free product. [Please, check 2nd example](https://docs.algolplus.com/algol_pricing/bogo-discount-help/)
= How to customize bulk tables or row "amount saved" =
You should copy necessary file from folder “BaseVersion/templates” to folder “advanced-dynamic-pricing-for-woocommerce” (create it in active theme)
= The rules are not applied to orders if I use button "Add order" (>WooCommerce>Orders) =
This form adds new order directly to the database. But all pricing plugins work with cart items. Use our plugin [Phone Orders](https://wordpress.org/plugins/phone-orders-for-woocommerce/) to add backend orders.
= I can't change quantity or delete item from cart =
It's a conflict with another plugin which modifies cart items too. You should turn on debugbar and send us report/json file. [Read short guide.](https://docs.algolplus.com/algol_pricing/debug/)
= I marked checkbox "Add products to cart at normal cost and add coupon...", but I don't see any coupons in the cart =
You should visit >WooCommerce>Settings and mark "Enable the use of coupon codes".
= I need custom cart condition =
You should be PHP programmer to do it. [Please, review sample addon and adapt it for your needs](https://docs.algolplus.com/algol_pricing/program-custom-condition/)
= I don't see my question   =
[Please, review full FAQ](https://docs.algolplus.com/algol_pricing/common-faq/)

== Screenshots ==
1. List of pricing rules
2. Rule type selector
3. Discount 5%, up to 10 euro
4. The rule was applied to the cart
5. Settings page


== Changelog ==

= 4.7.0 - 2024-03-25 =
* Speed up our plugin a bit
* UI tweak - allow to set same dates in rules header
* Bug fixed - column "Discounted price" had the same price in bulk table
* Bug fixed - shipping cost ignored in the cart if the rules were not applied and mode "Disable shipping calculation" active
* Updated compatibility with "YayCurrency", fatal error for new version
* Updated compatibility with "Woo Product Bundles", fatal error for priced individually bundles

= 4.6.2 - 2024-02-26 =
* Warning! Now field "To Date" (in rule header)  is LAST date for the rule
* Bug fixed -  fatal PHP error in ContainerCompatibilityManager.php, line 22
* Bug fixed -  bulk discount didn't work correctly for  "Product only" rules
* Bug fixed -  wrong notice that this plugin is not compatible with HPOS mode
* Bug fixed -  all sections were cleared if user pressed Enter in any field of the rule
* Bug fixed -  impossible to translate text above bulk table

= 4.6.1 - 2024-02-05 =
* Bug fixed -  PHP fatal error "undefined constant ATTR_TEMP"
* Bug fixed -  incorrect calculations if a volume discount or cart adjustment discount was added as a coupon
* Bug fixed -  same prices for all ranges in the bulk table if the discount amount was added as a coupon
* Bug fixed -  prices were doubled if options were added to the product using the “Woocommerce Product Add-Ons” plugin
* Bug fixed -  prices were doubled if the bundled product was created using the "Woocommerce Product Bundles" plugin
* Bug fixed -  PHP error for role-based discounts (rare case)
* Bug fixed -  "Products Only" rules were not cached
* Bug fixed -  same rule was being applied multiple times to the same cart item when an order was created using the Phone Orders plugin
* Bug fixed -  some phrases could not be translated

= 4.6.0 - 2024-01-16 =
* Fully recoded compatibility with the popular product bundle plugins
* Reduced size of section "exclude products" (inside product filters)
* Deleted unused option "Apply pricing rules while doing API request"
* Bug fixed - fatal PHP error for wrongly formatted date in cart condition "Date"
* Bug fixed - incorrect bulk table for default variation
* Added compatibility with "Klarna On-Site Messaging for WooCommerce" plugin, by krokedil
* Added compatibility with "WC Fields Factory", by Saravana Kumar K
* Updated compatibility with "TM Extra product options"
* Updated compatibility with "Yith WooCommerce gift cards"

= 4.5.4 - 2023-12-04 =
* Speed up generation of bulk table, at product page
* Bug fixed - wrong coupon amount for mode "Don't modify price/add as coupon"
* Bug fixed - conflict between option "Override cents" and rule checkbox "Don't modify price/add as coupon"
* Bug fixed - PHP8.2 deprecation warnings
* Updated compatibility with "YITH WooCommerce Gift Cards"

= 4.5.3 - 2023-10-30 =
* Bug fixed - can't view order in the backend , if WooCommerce Payments (version 6.6.0) is active
* Bug fixed - "individual use only" didn't work properly for WC coupons
* Bug fixed - can't deactivate WooCommerce Subscription plugin
* Added compatibility with "PPOM for WooCommerce", by Themeisle
* Updated compatibility with "WPML"
* Dev - added hook "adp_is_tax_exempt_processor_active", use it only if your custom code manages tax exemption

= 4.5.2 - 2023-10-10 =
* Minor UI tweaks at tab "Rules"
* Bug fixed - field "Sale Price" was overwritten when user used >Products>All Products>Export
* Bug fixed - zero "Amount Saved" at "Thank-you" page
* Updated compatibility with "WPML"
* Updated compatibility with "YITH WooCommerce Product Add-Ons"
* Dev - we pass raw $data_rows to template bulk-table.php (to simplify custom templates)

= 4.5.1 - 2023-09-11 =
* Critical bug fixed - it was impossible to save settings in section >Settings>Cart
* Bug fixed - incorrect display of the long names of the rules
* Bug fixed - incorrect display of "Add rule" button
* Bug fixed - PHP8 deprecation warnings
* Added compatibility with "Quote for WooCommerce", by WPExperts.io

= 4.5.0 - 2023-08-29 =
We are happy to announce the restyled version of our plugin! Advanced Dynamic Pricing is more user-friendly with this update

* Updated "Rules" tab (rules color, cache control buttons moved, "Add rule" button moved)
* Show discount type selection each time a rule is created
* The field "Max Amount" renamed to "Limit discount to amount" to avoid confusion
* Show an icon for each section of the rules
* Show the most used cart conditions in the Cart Conditions section
* Added search on the "Settings" tab
* Align sections vertically in the Settings and Tools tabs.
* The "Amount Saved" option has been moved from the Customizer to the cart/order settings
* Added 'Read-only quantity' option to free products settings
* Merged import/export settings into one "Backup" section inside the "Tools" tab
* Restyled sections in the "Help" tab

= 4.4.3 - 2023-07-31 =
* Bug fixed - multiple attributes worked incorrectly inside product filter
* Bug fixed - WooCommerce coupons caused "500 error" during checkout
* Bug fixed - conflict(empty cart) with Product Feed PRO for WooCommerce, by AdTribes.io
* Bug fixed - conflict(empty discount) with Points and Rewards for WooCommerce, by WPSwings
* Bug fixed - fee removed if user edited order using plugin "Phone Orders PRO"
* Bug fixed - section Limits didn't work when the rule applied as coupon
* Bug fixed - single quote soubled in discount name , section "Cart Adjustment"
* Bug fixed - missed order stats if applied coupons were merged
* Added compatibility with "Points and Rewards for WooCommerce", by WP Swings

= 4.4.2 - 2023-06-21 =
* Bug fixed - free shipping ignored if it was added by WooCommerce сoupon
* Bug fixed - import CSV failed if field "To range" was empty for bulk rule
* Bug fixed - deprecation notices in PHP 8.1
* Added compatibility with "Variation Swatches for WooCommerce", by Emran Ahmed
* Added (multi-currency) compatibility with "WooCommerce Payments", by WooCommerce
* Updated compatibility with "Acowebs Custom Product Addons", to support version 5.x

= 4.4.1 - 2023-05-25 =
* Critical bug fixed - discount doubled for WooCommerce сoupons
* Updated code for [adp_products_on_sale] and [adp_products_bogo] shortcodes

= 4.4.0 - 2023-05-22 =
* "Products" - the default value for the new filter (section "Filter by products")
* Bug fixed - the "Free shipping" rule was not applied to the created order
* Bug fixed - variant name does not show attributes if this variant has 3+ attributes
* Bug fixed - exported rules were skipping "Cart setup" section
* Bug fixed - divide-by-zero error for products with zero price inside a bundle
* Bug fixed - fatal PHP error in Processor.php file, line 357 (only for rules with free products)
* Added compatibility with "YayCurrency - WooCommerce Multi-Currency Switcher", by YayCommerce
* Updated compatibility with "Additional product options and add-ons for WooCommerce"
* Updated compatibility with "Acowebs Custom Product Addons", fixed some php warnings
* Updated compatibility with "WPML", added hook "adp_translate_rules"

= 4.3.2 - 2023-04-19 =
* internal, not published

= 4.3.1 - 2023-04-04 =
* Added selector "When the striked price should be shown" to section >Settings>Product Price. [More details](https://docs.algolplus.com/algol_pricing/when-the-striked-price-should-be-shown/)
* Added/updated sections in Customizer
* Bug fixed - wrong "Amount Saved" displayed if option "Override the cents on the calculated price" was active
* Bug fixed - WooCommerce REST API failed with error 500 in ShippingController.php
* Bug fixed - impossible to hide fixed price for bulk table, in mode "Display ranges as headers"
* Bug fixed - PHP fatal error "undefined function wc_get_notices()"
* Added compatibility with "Mix and Match Products", by Backcourt Development
* Added compatibility with "MyRewards - Loyalty Points and Rewards for WooCommerce", by Long Watch Studio
* Updated compatibility with "WooCommerce Price Based on Country", "Woocommerce Custom Product Addons" and Shoptimizer theme

= 4.3.0 - 2023-01-24 =
* Support High-Performance order storage (COT)
* Bug fixed - option "Override the cents on the calculated price" didn't work at product page
* Bug fixed - spliited items(same product!) should be next to each other
* Bug fixed - WPC Product Bundles were ignored in the conditions
* Added compatibility with "YITH WooCommerce Product Add-Ons", by YITH
* Added compatibility with "YITH WooCommerce Product Bundles", by YITH
* Updated compatibility with "Aelia Currency Switcher"
* Updated compatibility with [Phone Orders](https://wordpress.org/plugins/phone-orders-for-woocommerce/)
* Rewrite compatibility with Polylang and WPML plugins
* Support mode "Display ranges as headers" for shortcode [adp_category_bulk_rules_table]