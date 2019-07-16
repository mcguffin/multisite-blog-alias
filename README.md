Multisite Blog Alias
====================

This is the official github repository of the [Multisite Blog Alias](https://wordpress.org/plugins/multisite-blog-alias/) plugin.

WordPress Multisite plugin to maintain URL-redirects for Blogs.

Features
--------
 - Permanent 301 Redirect to blogs main domain
 - Painless installation and activation – no file access necessary.
 - Checks domain status
 - [WP-Cli](https://wp-cli.org/) commands

WP-CLI
------

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


Installation
------------

### Development
 - cd into your plugin directory
 - $ `git clone git@github.com:mcguffin/multisite-blog-alias.git`
 - $ `cd multisite-blog-alias`
 - $ `npm install && npm run dev`
