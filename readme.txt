=== Multisite Blog Alias ===
Contributors: podpirate
Donate link: https://www.msf.org/donate
Tags: network, redirect, multisite, domain
Requires at least: 4.8
Requires PHP: 5.5
Tested up to: 5.2.2
Stable tag: 1.0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Set up redirects for Multisite-Blogs.

== Description ==

WP Multisite plugin to maintain URL-redirects for Blogs.

## Features
 - Permanent 301 Redirect to blogs main domain
 - Painless installation and activation – no file access necessary.
 - Checks domain status
 - [WP-Cli](https://wp-cli.org/) commands

## Usage
1. **Set up your DNS**. Make sure your Domain points to your Wordpress installation. You can achieve this by either setting up an A- or CNAME-Record in your DNS configuration. Your webserver must be configured to handle requests on the given Domain.
2. Under **Network Admin – Sites** edit the site and select the Alias Domains Tab.
3. Enter the domain without `http` ao `/` and click "Add".
4. Click "Check Status" to see if it worked. If something went wrong you will show an error message.

If you want to redirect with URL path appended (e.g. from `some-alias.tld/some/path` to `some-real-blog.tld/some/path`), add this to your `wp-config.php`:

    define( 'WPMU_BLOG_ALIAS_REDIRECT_WITH_PATH', true );

#### Status messages

**Warning: The domain matches the site URL of this blog:** The Blog is using the domain name as Site URL.

**Error: The domain is already used by another site:** A different Blog is already using the domain as Site URL. Eiter Remove the alias from the sblog you are currently workin on, or from the other one.

**The domain is unreachable:** There is likely an error in your DNS or your Webserver configuration. Use `nslookup` from he command line or [whatsmydns.net](https://www.whatsmydns.net/) to check the DNS-Settings.

**The domain or a redirect does not point to this blog:** Following all redirects did not end up on your WordPress-Site, but somewhere else. There is likely an error in your DNS or your Webserver configuration.

## WP-CLI Examples
### Listing Domain aliases

**List alias domains for blog-id 123**

    wp alias-domains list --blog_id=123

**Output minified json of all aliases**

    wp alias-domains list --format=json --compact=2

**Output csv including the header row but omitting other messages into file**

    wp alias-domains list --format=csv --compact > alias-list.csv

### Add Domain Alias

    wp alias-domains add --blog_id=123 --domain_alias=quux.foobar.tld

### Remove Domain Alias
**Remove a specific alias**

    wp alias-domains remove --domain_alias=quux.foobar.tld

**Remove all aliases for blog 123**

    wp alias-domains remove --blog_id=123

### Testing Domain Aliases

    wp alias-domains test --domain_alias=quux.foobar.tld


## Development

Please head over to the source code [on Github](https://github.com/mcguffin/multisite-blog-alias).

== Installation ==

Follow the standard [WordPress plugin installation procedere](http://codex.wordpress.org/Managing_Plugins).

The installer will:
1. Create a database table `{$table_prefix}alias_domains`
2. Create a file `wp-content/sunrise.php` or append its PHP to it, if the file already exists.
3. Insert `define( 'SUNRISE', true );` in your wp-config.

== Frequently asked questions ==

= I found a bug. Where should I post it? =

Please use the issues section in the [GitHub-Repository](https://github.com/mcguffin/multisite-blog-alias/issues).

I will most likely not maintain the forum support forum on wordpress.org. Anyway, other users might have an answer for you, so it's worth a shot.

= I'd like to suggest a feature. Where should I post it? =

Please post an issue in the [GitHub-Repository](https://github.com/mcguffin/multisite-blog-alias/issues)

= Will you anwser support requests by email? =

No.


== Screenshots ==
1. Network admin - Edit site

== Upgrade Notice ==

On the whole upgrading is always a good idea.

== Changelog ==

= 1.0.1 =
- Send `X-Redirect-By` HTTP Header
- Option to redirect with path using constant `WPMU_BLOG_ALIAS_REDIRECT_WITH_PATH`.
- Fix: PHP Fatal in sunrise.php if formatting functions are not present
- Fix: Make sure Status check ah´jax is loaded from Network-URL
- Validation: Make sure only hostnames can be entered

= 1.0.0 =
- Initial release
