=== Multisite Blog Alias ===
Contributors: podpirate
Donate link: https://www.msf.org/donate
Tags: network, redirect, multisite, domain
Requires at least: 4.8
Requires PHP: 5.5
Tested up to: 5.2.2
Stable tag: 0.3.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Set up redirects for Multisite-Blogs.

== Description ==

WP Multisite plugin to maintain URL-redirects .

## Features
 - Permanent 301 Redirect to blogs main domain
 - Painless installation and activation – no file access necessary.
 - Checks domain status
 - [WP-Cli](https://wp-cli.org/) commands

## WP-CLI Examples
### Listing Domain aliases

List alias domains for blog-id 123
```
wp alias-domains list --blog_id=123
```

Output all aliases as minified json
```
wp alias-domains list --format=json --compact=2
```

Output csv including the header row but omitting other messages into file
```
wp alias-domains list --format=csv --compact > alias-list.csv
```

### Add Domain Alias
```
wp alias-domains add --blog_id=123 --domain_alias=quux.foobar.tld
```

### Remove Domain Alias
Remove a specific alias
```
wp alias-domains remove --domain_alias=quux.foobar.tld
```

Remove all aliases for blog 123
```
wp alias-domains remove --blog_id=123
```

### Testing Domain Aliases
```
wp alias-domains test --domain_alias=quux.foobar.tld
```


## Development

Please head over to the source code [on Github](https://github.com/mcguffin/multisite-blog-alias).

== Installation ==

Follow the standard [WordPress plugin installation procedere](http://codex.wordpress.org/Managing_Plugins).

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

= 0.3.0 =
- Feature: wp-cli - introduce test command
- Feature: wp-cli add - introduce --compact arg
- Fix: wp-cli - don't add if domain used by another blog

= 0.3.2 =
- Check for multisite on activation
- Fix subdirectory install
- Fix redirect check
- Fix i18n issue

= 0.3.1 =
- WP-Cli: args for list output format

= 0.3.0 =
- Feature: Show Domain status
- More Failsafe sunrise
- Fix db error message on activation
