Seamless Schema
===============

* Contributors: nicolas.bernier
* Tags: Schema.org, OpenGraph, tag, SEO
* Requires at least: 3.0.1
* Tested up to: 3.9.1
* License: GPLv2 or later
* License URI: [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)

Seamlessly insert schema.org and Open Graph microdata into WordPress.

Description
===========

Seamless schema automatically inserts Schema.org and Open Graph metadata inside the page header to improve SEO and social network display. The metadata is extracted by default from the blog content data such as the page title, the post excerpt, the thumbnail image but it can be completed for each blog post, page and attachment using the provided metadata editor for simple properties and even within the page's HTML code for the more complex ones.

Installation
============

1. Download and unzip seamless-schema archive to the `/wp-content/plugins/` directory or add it using WordPress' plugin manager.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. It works!

Configuration
=============

Configure the default page type and metadata and for the homepage. By clicking *Settings* / *Seamless Schema* in the admin page. This is optional since Wordpress' values (page title, description and header image) are used by default.

If you have Polylang installed, you can set per-language settings.

How to use
==========

When editing a blog post, a page or an attachment, you can set its Schema.org content type and metadata. In most cases, the content-type will be *Article* or *Web Page* but you can set a more accurate type such as *Organization* or *Product Review*.

You can add extra metadata and override the default ones such as the name, description (post excerpt) and image by selecting the property you want in the select box then click the + button. The properties that already have a default value show up in italic in the list. If you set an invalid property for the selected content type, the row will show up in red and won't be used on the website.

The metadata is added in meta tags in the page header so it's totally invisible. However, only canonical properties can be added. For more complex types such as home address and product review, you have to add manually the metadata in the HTML code of your post. Have a look at the Schema.org website for more information about the structure.

Additional OpenGraph tags can be added in posts, pages and attachments as custom fields prefixed by `og_`. For example, to add the tag `og:my_tag`, add a custom field named `og_og:my_tag` with the value you want.

Frequently Asked Questions
==========================

How can I validate the Schema.org metadata of my web page ?
-----------------------------------------------------------

* For Schema.org metadata, go to [http://www.google.com/webmasters/tools/richsnippets](http://www.google.com/webmasters/tools/richsnippets)
* For OpenGraph metadata, go to [https://developers.facebook.com/tools/debug/](https://developers.facebook.com/tools/debug/)

I have Schema.org validation errors. What can I do?
---------------------------------------------------

Your theme may already have its own Schema.org implementation that causes issues. Try with another theme or disable Seamless Schema.

Screenshots
-----------

1. The Schema.org metadata editor

Changelog
---------

### 1.0

* First public release