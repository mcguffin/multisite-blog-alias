WP Blog Alias
===============

Multisite plugin to maintain alternative Domains for a blog.

Blog Aliases will redirect to the blog they belong to.

Painless installation and activation – makes all necessary changes to `wp-config.php` and `wp-content/sunrise.php`.

wp-cli integration.

ToDo:
-----
 - [ ] Test with path install
 - [ ] Translate


Installation
------------

### Production Install (using Github Updater)
 - Install [Andy Fragen's GitHub Updater](https://github.com/afragen/github-updater) first.
 - In WP Admin go to Settings / GitHub Updater / Install Plugin. Enter `mcguffin/wp-blog-alias` as a Plugin-URI.

### Development
 - cd into your plugin directory
 - $ `git clone git@github.com:mcguffin/wp-blog-alias.git`