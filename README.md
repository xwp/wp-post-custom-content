Custom (Unfiltered) Content for Individual Posts
=================

This plugin allows you to add (unfiltered) content into one or more fields for individual posts (or other post types) and then place that content into the post body by a short code. You can also have the content of those fields automatically appended to the content of the post.

The fields (textarea) are powered by the [ACE editor](http://ace.c9.io).

#Requirements#

[Same requirements as WordPress](http://wordpress.org/about/requirements/) plus PHP 5.3+

#Configuration#

**Supported Post Types**

The custom content fields are automatically added to all post types (stock and custom) that have support for 'editor'.

**Short Code**

The default short code is [custom_content]. If you find that it is conflicting with another plugin you can switch it out by adding he following function to your theme's functions.php or a plugin.

```php
function my_custom_content_shortcode_tag( $shortcode ) {
	return 'my_custom_not_conflicting_content_shortcode_tag';
}
add_filter( 'custom_content_shortcode_tag', 'my_custom_content_shortcode_tag' );
```
