=== VerbatimAI Meta Tags Optimizer ===
Contributors: yuvarajr, ownyu
Donate link: https://www.ownyu.com/products/ai-meta-tags-optimizer
Tags: seo, ai overviews, meta tags, schema, google ai
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize your posts and pages for Google AI Overviews and LLM-based search engines with targeted summaries, Q&A schema, and entity keywords.

== Description ==

**VerbatimAI Meta Tags Optimizer** helps you prepare your WordPress content for the new era of AI-driven search: Google AI Overviews, Bing Copilot, ChatGPT browsing, Perplexity, and other large language model (LLM) crawlers.

Traditional SEO meta tags were built for classic search engine result pages. AI Overviews and LLM-based answer engines look for concise, factual, well-structured information they can extract and cite directly. This plugin gives you a simple editorial workflow to provide that information explicitly, on a per-post or per-page basis, without touching a line of code.

= Key Features =

* **AI-Targeted Summary / TL;DR** — Write a short, factual summary of your content specifically for AI scraping and snippet extraction.
* **Key Q&A for AI Search** — Add an unlimited number of Question and Answer pairs. These are automatically converted into `FAQPage` JSON-LD schema, the format AI Overviews frequently prioritize.
* **Custom Meta Keywords / Entities** — Define the core entities, brands, products, and topics associated with your content.
* **Automatic Frontend Injection** — The plugin automatically outputs standard HTML `<meta>` tags and a structured `JSON-LD` schema script in your site's `<head>` on every relevant single post or page.
* **One-Click Copy to Clipboard** — Instantly copy the generated meta tags and JSON-LD schema block to your clipboard for manual use elsewhere (newsletters, AMP pages, third-party platforms, etc.).
* **Lightweight & Self-Contained** — No external dependencies, no settings sprawl, no bloat. Built using WordPress core APIs only.
* **Secure by Design** — All inputs are sanitized, all outputs are escaped, and all save actions are protected by WordPress nonces and capability checks.

= How It Works =

1. Edit any Post or Page.
2. Find the "VerbatimAI Overview Optimizer" meta box below the content editor.
3. Fill in your AI summary, Q&A pairs, and entity keywords.
4. Update or Publish the post — the plugin automatically injects the optimized meta tags and JSON-LD schema into your page's `<head>`.
5. Optionally, click "Copy AI Meta Data" to grab a copy of the generated tags for manual use elsewhere.

= Why Optimize for AI Overviews? =

AI Overviews and LLM-based answer engines increasingly determine what content gets surfaced, summarized, and cited in response to user queries. By providing clear, structured, machine-readable signals — a concise summary, explicit Q&A pairs, and named entities — you increase the likelihood that AI systems correctly understand, extract, and attribute your content.

This plugin is developed and maintained by VerbatimAI.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin dashboard.
2. Navigate to **Plugins → Add New**.
3. Search for **"VerbatimAI Meta Tags Optimizer"**.
4. Click **Install Now**, then **Activate**.

= Manual Installation =

1. Download the plugin ZIP file.
2. Navigate to **Plugins → Add New → Upload Plugin** in your WordPress admin dashboard.
3. Select the downloaded ZIP file and click **Install Now**.
4. Activate the plugin through the **Plugins** menu in WordPress.

= After Activation =

1. Edit any existing Post or Page, or create a new one.
2. Scroll down to the **"VerbatimAI Overview Optimizer"** meta box.
3. Fill in the AI Summary, Q&A pairs, and Entity Keywords fields.
4. Click **Update** or **Publish**.

== Frequently Asked Questions ==

= Does this plugin replace my existing SEO plugin? =

No. VerbatimAI Meta Tags Optimizer is designed to work alongside popular SEO plugins (such as Yoast SEO or Rank Math). It adds a separate, complementary layer of AI-specific meta tags and JSON-LD schema focused on AI Overviews and LLM search, without modifying or conflicting with your existing SEO titles, meta descriptions, or sitemaps.

= Where do the generated meta tags appear? =

The plugin hooks into `wp_head` and automatically outputs standard HTML `<meta name="ai-summary">`, `<meta name="ai-entities">` tags, and a `application/ld+json` script tag containing `Article` and `FAQPage` structured data, directly in the `<head>` section of the relevant single post or page.

= What if I leave a field empty? =

Each field is optional. If you leave the summary, entities, and Q&A fields all empty, the plugin will not output anything to the page head for that post, keeping your markup clean.

= Does the "Copy AI Meta Data" button submit the form? =

No. The Copy button runs entirely in your browser using JavaScript. It reads the current values in the fields, builds the meta tag and JSON-LD output, and copies it to your clipboard. It does not save the post or reload the page.

= Is the plugin translation-ready? =

Yes. All user-facing strings are wrapped in WordPress translation functions and the plugin uses the `verbatimai-meta-tags-optimizer` text domain.

= Does this plugin track or send any data externally? =

No. The plugin does not collect, transmit, or share any data with external servers. All data you enter is stored locally in your WordPress database as standard post meta.

= Is this plugin compatible with custom post types? =

Version 1.0.0 supports the default **Post** and **Page** post types. Support for custom post types may be added in a future release.

== Screenshots ==

1. The "VerbatimAI Overview Optimizer" meta box on the Post edit screen, showing the summary, Q&A repeater, and entities fields.
2. The "Copy AI Meta Data" button with a success confirmation message after copying.
3. Example of the generated JSON-LD schema and meta tags as rendered in a page's HTML source.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Added "VerbatimAI Overview Optimizer" meta box with AI Summary, Q&A repeater, and Entity Keywords fields.
* Added automatic `wp_head` injection of `ai-summary` and `ai-entities` meta tags.
* Added automatic JSON-LD `Article` and `FAQPage` schema generation for AI Overviews and LLM search engines.
* Added one-click "Copy AI Meta Data" button with clipboard support and fallback.
* Implemented nonce verification, capability checks, input sanitization, and output escaping throughout.

== Upgrade Notice ==

= 1.0.0 =
Initial release of VerbatimAI Meta Tags Optimizer. No upgrade steps required.
