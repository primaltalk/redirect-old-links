<?php
/**
 * Plugin Name:     Redirect Old Links
 * Description:     Did you change your permalink structure and now have old links "404ing"? I'll  redirect those old links to their new one for you.
 * Plugin URI:      https://github.com/ryanshoover/redirect-old-links
 * Author:          Ryan Hoover
 * Author URI:      https://github.com/ryanshoover
 * Version:         1.0.0
 * License:         GPL3
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace RedirectOldLinks;

/**
 * Redirect 404s that end in the post name
 *
 * If a request comes in that is a 404, but the last
 * subdirectory is a post's slug, then redirect to
 * that post's actual link
 */
function maybe_redirect_404_links() {
	// If this isn't a 404 error page, abort
	if ( 404 != get_query_var( 'error' ) ) {
		return;
	}

	// Get the request path's parts
	$request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	$request_parts = explode( '/', $request_uri );
	$request_parts = array_filter( $request_parts );

	// If we don't have any parts, abort
	if ( empty( $request_parts ) ) {
		return;
	}

	// Find the post that has the matching slug
	$post_name = array_pop( $request_parts );
	$query = array(
		'name'           => $post_name,
		'post_type'      => 'any',
		'posts_per_page' => 1,
	);
	$posts = get_posts( $query );

	// If we didn't find a post, check the network.
	$blog_id = get_current_blog_id();
	if ( empty( $posts ) ) {
		$sites = get_sites(array(
			'site__not_in' => array($blog_id)
		));
		foreach($sites as $site) {
			switch_to_blog($site->blog_id);
			$posts = get_posts($query);
			if(!empty($posts)) {
				break;
			}
		}
		if(empty($posts)) {
			switch_to_blog($blog_id);
			return;
		}
	}

	$post = array_shift( $posts );

	// Redirect to the post's actual link
	$permalink = get_permalink( $post->ID );
	switch_to_blog($blog_id);
	wp_redirect( $permalink, 301 );
	exit;
}

add_action( 'template_redirect', 'RedirectOldLinks\maybe_redirect_404_links' );
