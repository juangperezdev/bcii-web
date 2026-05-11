<?php
/**
 * BCII — Arregla dos cosas en cada página interna:
 *  1. Quita la clase .reveal del image widget (causaba que la imagen
 *     desapareciera al click en el editor Elementor).
 *  2. Vuelve la primera section a layout: full_width — así el page-hero
 *     extiende su bg warm de borde a borde como antes (no constreñido al
 *     boxed default ~1140px).
 *  3. Quita el background_color del Elementor section settings (lo da el
 *     `<section class="page-hero">` interno).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pages = array( 258, 10009, 10010, 10011, 10012, 10013, 10014, 10015, 10016, 10017 );

foreach ( $pages as $pid ) {
    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) continue;
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data[0] ) ) continue;

    /* 1) Quitar reveal del image widget */
    $col0 = $data[0]['elements'][0] ?? null;
    if ( $col0 ) {
        foreach ( $col0['elements'] as $i => $w ) {
            if ( ( $w['widgetType'] ?? '' ) === 'image' ) {
                $cls = (string)( $w['settings']['_css_classes'] ?? '' );
                $cls = trim( preg_replace( '/\s*\breveal\b\s*/', ' ', $cls ) );
                $cls = preg_replace( '/\s+/', ' ', $cls );
                $data[0]['elements'][0]['elements'][ $i ]['settings']['_css_classes'] = $cls;
            }
        }
    }

    /* 2) layout = full_width */
    $s = $data[0]['settings'] ?? array();
    $s['layout']  = 'full_width';
    /* 3) quitar el bg_color del Elementor section (page-hero ya lo tiene) */
    unset( $s['background_background'], $s['background_color'] );
    $data[0]['settings'] = $s;

    update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES ) ) );
    delete_post_meta( $pid, '_elementor_css' );
    WP_CLI::success( "page {$pid} → reveal removido + section full_width" );
}
