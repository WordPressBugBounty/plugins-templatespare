<?php

function templatespare_import_navigation() {
    // Get front page ID
    $front_page_id = null;
    $front_page_query = new WP_Query( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'title'          => 'Home',
    ) );
    if ( $front_page_query->have_posts() ) {
        // If a page with the title 'Home' exists, check for similar slugs
        while ( $front_page_query->have_posts() ) {
            $front_page_query->the_post();
            if ( get_post_field( 'post_name' ) === 'home' ) {
                // If a page with slug 'home' exists, set its ID
                $front_page_id = get_the_ID();
                break;
            }
        }
        // If no page with slug 'home' exists, set the ID of the first page found
        if ( ! $front_page_id ) {
            $front_page_id = $front_page_query->posts[0]->ID;
        }
        wp_reset_postdata(); // Reset the query
    }

    // Get blog page ID
    $blog_page_id = null;
    $blog_page_query = new WP_Query( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'title'          => 'Blog',
    ) );
    if ( $blog_page_query->have_posts() ) {
        $blog_page_id = $blog_page_query->posts[0]->ID;
    }

    // Update options for front page and blog page
    if ( $front_page_id ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front_page_id );
    }

    if ( $blog_page_id ) {
        update_option( 'page_for_posts', $blog_page_id );
    }

    // Update navigation menus
    $registered_menus = get_registered_nav_menus();
    $nav_menus        = get_terms( 'nav_menu', array( 'hide_empty' => true ) );

    $new_menu = array();
    foreach ( $registered_menus as $location => $description ) {
        foreach ( $nav_menus as $menu ) {
            // Check if the menu slug matches the page slug
            if ( $front_page_id && get_post_field( 'post_name', $front_page_id ) === 'home' && strpos( $location, 'primary' ) !== false ) {
                $new_menu[ $location ] = $menu->term_id;
            } elseif ( $blog_page_id && get_post_field( 'post_name', $blog_page_id ) === 'blog' && strpos( $location, 'secondary' ) !== false ) {
                $new_menu[ $location ] = $menu->term_id;
            }
        }
    }
    set_theme_mod( 'nav_menu_locations', $new_menu );
}


add_action('templatespare/after_import', 'templatespare_import_navigation');

add_filter( 'templatespare_post_content_before_insert', 'templatespare_replace_urls', 10, 2 );

function templatespare_replace_urls($content, $old_base_url ){
   
    $site_url = get_site_url();
    $site_url = str_replace( '/', '\/', $site_url );
   
    $demo_site_url = str_replace( '/', '\/', $old_base_url );
    $content = json_encode($content,true);
    
    $content = preg_replace('/\\\{1}\/sites\\\{1}\/\d+/', '', $content);
    
    $content = str_replace( $demo_site_url, $site_url, $content );
    
    $content = json_decode( $content, true );
    
    return $content;
       
}

