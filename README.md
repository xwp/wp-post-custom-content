Custom (Unfiltered) Content for Individual Posts
=================

This plugin allows you to add (unfiltered) content into one or more fields for individual posts (or other post types) and then place that content into the post body by a short code. You can also have the content of those fields automatically appended to the content of the post.

#Requirements#

[Same requirements as WordPress](http://wordpress.org/about/requirements/) plus PHP 5.3+

#Post Types#

If you need to add these fields to another post type (for example 'event' you can do that by adding this function to your theme or plugin:

```php
function add_additional_post_types( $post_types ) {
	$post_types['event'] = true;
	return $post_types;
}
add_filter( 'custom_content_post_types', 'add_additional_post_types' );
```
