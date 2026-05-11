<?php
/**
 * BCII — Revierte el último swap: vuelve a poner Image primero, HTML segundo
 * en la primera sección de las páginas internas.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/revert-pagehero-order.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pages = array( 258, 10009, 10010, 10011, 10012, 10013, 10014 );

foreach ( $pages as $pid ) {
    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) continue;

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data[0] ) ) continue;

    $col_idx = 0;
    $col     = $data[0]['elements'][ $col_idx ] ?? null;
    if ( ! $col ) continue;

    $widgets = $col['elements'] ?? array();
    if ( count( $widgets ) < 2 ) continue;

    $w0 = $widgets[0];
    $w1 = $widgets[1];

    if ( ( $w0['widgetType'] ?? '' ) === 'image' && ( $w1['widgetType'] ?? '' ) === 'html' ) {
        WP_CLI::log( "[done] page {$pid} — ya está image → html" );
        continue;
    }
    if ( ( $w0['widgetType'] ?? '' ) === 'html' && ( $w1['widgetType'] ?? '' ) === 'image' ) {
        $widgets[0] = $w1;
        $widgets[1] = $w0;
        $data[0]['elements'][ $col_idx ]['elements'] = $widgets;
        $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
        update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
        delete_post_meta( $pid, '_elementor_css' );
        WP_CLI::success( "page {$pid} → revertido: imagen primero, texto debajo" );
    }
}
WP_CLI::log( 'Done.' );
