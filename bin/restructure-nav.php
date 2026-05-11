<?php
/**
 * BCII — Restructura el menú "Primary" al estilo Evernote:
 *   4 top-level items con dropdowns para secciones secundarias.
 *
 *   About
 *   Platform ▾   → Token, Platform, Strategy, Business Model, Market
 *   Investors ▾  → Thesis, Leadership, Investor Relations
 *   News
 *
 * El CTA principal ("Investment Thesis") lo renderiza header.php por separado,
 * NO vive en el menú.
 *
 * Idempotente: borra todos los items existentes del menú Primary antes de
 * reconstruir.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/restructure-nav.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$primary = wp_get_nav_menu_object( 'Primary' );
if ( ! $primary ) {
    WP_CLI::error( 'No existe el menú "Primary"' );
}
$menu_id = (int) $primary->term_id;

/* 1) Limpiar items existentes */
$existing = wp_get_nav_menu_items( $menu_id );
if ( is_array( $existing ) ) {
    foreach ( $existing as $it ) {
        wp_delete_post( $it->ID, true );
    }
}
WP_CLI::log( "menu '{$primary->name}' (#{$menu_id}) — items previos borrados: " . ( is_array( $existing ) ? count( $existing ) : 0 ) );

/* 2) Helper para agregar items */
$add = function( $args ) use ( $menu_id ) {
    $defaults = array(
        'menu-item-status' => 'publish',
    );
    return wp_update_nav_menu_item( $menu_id, 0, array_merge( $defaults, $args ) );
};

$page = function( $id, $parent = 0 ) use ( $add ) {
    return $add( array(
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $id,
        'menu-item-type'      => 'post_type',
        'menu-item-parent-id' => $parent,
    ) );
};

$custom = function( $title, $parent = 0 ) use ( $add ) {
    return $add( array(
        'menu-item-title'     => $title,
        'menu-item-url'       => '#',
        'menu-item-type'      => 'custom',
        'menu-item-parent-id' => $parent,
    ) );
};

/* 3) Construir estructura */
$about_id = $page( 258 );

$platform_parent = $custom( 'Platform' );
$page( 10009, $platform_parent ); // Token
$page( 10010, $platform_parent ); // Platform
$page( 10011, $platform_parent ); // Strategy (advertising)
$page( 10012, $platform_parent ); // Business Model
$page( 10013, $platform_parent ); // Market

$investors_parent = $custom( 'Investors' );
$page( 10014, $investors_parent ); // Thesis (investment)
$page( 10015, $investors_parent ); // Leadership
$page( 10017, $investors_parent ); // Investor Relations

$news_id = $page( 10016 );

WP_CLI::success( "menu 'Primary' reconstruido con 4 top-level + 8 children" );
