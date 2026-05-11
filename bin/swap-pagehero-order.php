<?php
/**
 * BCII — Swappea el orden de los widgets dentro de la primera sección de
 * cada página interna: TEXTO primero, IMAGEN debajo (siempre dentro de la
 * misma sección).
 *
 * Antes (estado anterior):
 *   Section 0 → [Image widget banner] [HTML widget page-hero]
 *
 * Después (objetivo):
 *   Section 0 → [HTML widget page-hero] [Image widget banner]
 *
 * Idempotente.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/swap-pagehero-order.php
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
    if ( count( $widgets ) < 2 ) {
        WP_CLI::log( "[skip] page {$pid} — primera sección con menos de 2 widgets" );
        continue;
    }

    $w0 = $widgets[0];
    $w1 = $widgets[1];

    $w0_type = $w0['widgetType'] ?? '';
    $w1_type = $w1['widgetType'] ?? '';

    /* Idempotencia: ya en el orden HTML → Image */
    if ( $w0_type === 'html' && $w1_type === 'image' ) {
        WP_CLI::log( "[done] page {$pid} — ya está texto → imagen" );
        continue;
    }

    /* Si el orden actual es Image → HTML, swappear */
    if ( $w0_type === 'image' && $w1_type === 'html' ) {
        $widgets[0] = $w1;
        $widgets[1] = $w0;
        $data[0]['elements'][ $col_idx ]['elements'] = $widgets;

        $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
        update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
        delete_post_meta( $pid, '_elementor_css' );

        WP_CLI::success( "page {$pid} → swappeado: texto primero, imagen debajo" );
        continue;
    }

    WP_CLI::warning( "[skip] page {$pid} — orden inesperado: {$w0_type} → {$w1_type}" );
}

WP_CLI::log( 'Done.' );
