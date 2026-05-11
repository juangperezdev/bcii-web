<?php
/**
 * BCII — Revierte la imagen del page-hero a una sección separada debajo.
 *
 * Estado actual:
 *   Section 0 (full_width, padding 0):
 *     [Image widget banner] [HTML page-hero is-with-banner]
 *
 * Después de este script:
 *   Section 0 (default):
 *     [HTML page-hero]                        ← solo texto, como original
 *   Section 1 (bcii-page-illustration band):
 *     [Image widget bcii-hero-media reveal]   ← imagen como sección separada
 *
 * Idempotente.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/revert-image-to-separate-section.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pages = array( 258, 10009, 10010, 10011, 10012, 10013, 10014 );

$uid = function() { return substr( md5( uniqid( '', true ) . mt_rand() ), 0, 7 ); };

foreach ( $pages as $pid ) {
    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) continue;

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data[0] ) ) continue;

    /* Idempotencia: si la primera sección sólo tiene un HTML widget (sin Image),
     * y ya hay una sección bcii-page-illustration, skip. */
    $col0      = $data[0]['elements'][0] ?? null;
    $widgets0  = $col0['elements'] ?? array();
    $is_done   = (
        count( $widgets0 ) === 1 &&
        ( $widgets0[0]['widgetType'] ?? '' ) === 'html' &&
        strpos( (string)( $widgets0[0]['settings']['html'] ?? '' ), 'is-with-banner' ) === false
    );
    if ( $is_done ) {
        WP_CLI::log( "[done] page {$pid} — page-hero ya está sin imagen mergeada" );
        continue;
    }

    /* Buscar el image widget en la primera sección */
    $image_widget = null;
    $kept_html    = null;
    foreach ( $widgets0 as $w ) {
        if ( ( $w['widgetType'] ?? '' ) === 'image' && strpos( (string)( $w['settings']['_css_classes'] ?? '' ), 'bcii-hero-media' ) !== false ) {
            $image_widget = $w;
        } elseif ( ( $w['widgetType'] ?? '' ) === 'html' ) {
            $kept_html = $w;
        }
    }
    if ( ! $image_widget || ! $kept_html ) {
        WP_CLI::warning( "[skip] page {$pid} — no encontré image+html en sección 0" );
        continue;
    }

    /* Limpiar el HTML del page-hero: quitar la clase is-with-banner */
    $h = $kept_html['settings']['html'] ?? '';
    $h = str_replace( '<section class="page-hero is-with-banner"', '<section class="page-hero"', $h );
    $kept_html['settings']['html'] = $h;

    /* Reconstruir sección 0: una sola columna con el HTML widget. Volver a
     * settings default (boxed + padding 0 ya estaba). */
    $orig_settings = $data[0]['settings'] ?? array();
    unset( $orig_settings['layout'] );        // eliminar full_width → vuelve a boxed
    $data[0]['settings'] = array_merge( $orig_settings, array(
        'gap'              => 'no',
        'structure'        => '10',
        'content_position' => 'top',
        'padding'          => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
    ) );
    $data[0]['elements'][0]['elements'] = array( $kept_html );

    /* Crear nueva sección 1 con la banda imagen (estilo bcii-page-illustration) */
    /* Refrescar settings del image widget para asegurarnos de quitar is-banner */
    $cls = (string)( $image_widget['settings']['_css_classes'] ?? '' );
    $cls = trim( str_replace( array( 'is-banner', '  ' ), array( '', ' ' ), $cls ) );
    if ( strpos( $cls, 'bcii-hero-media' ) === false ) $cls = 'bcii-hero-media reveal';
    if ( strpos( $cls, 'reveal' ) === false ) $cls .= ' reveal';
    $image_widget['settings']['_css_classes'] = $cls;

    $new_section = array(
        'id'       => $uid(),
        'elType'   => 'section',
        'settings' => array(
            'gap'              => 'no',
            'structure'        => '10',
            'content_position' => 'top',
            '_css_classes'     => 'bcii-page-illustration',
            'padding'          => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
        ),
        'elements' => array(
            array(
                'id'       => $uid(),
                'elType'   => 'column',
                'settings' => array(
                    '_column_size' => 100,
                    '_inline_size' => null,
                    'padding'      => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
                ),
                'elements' => array( $image_widget ),
                'isInner'  => false,
            ),
        ),
        'isInner' => false,
    );

    array_splice( $data, 1, 0, array( $new_section ) );

    $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
    update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
    delete_post_meta( $pid, '_elementor_css' );

    WP_CLI::success( "page {$pid} → imagen movida a sección separada (sección 1)" );
}
WP_CLI::log( 'Done.' );
