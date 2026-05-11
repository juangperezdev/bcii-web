<?php
/**
 * BCII — Convierte la banda hero de cada página interna a un Widget Image
 * nativo de Elementor (editable visualmente desde el editor Elementor).
 *
 *   wp-admin → Pages → editar con Elementor → click sobre la imagen → panel
 *   izquierdo "Edit Image" → cambiar archivo → Update.
 *   Sin HTML. Sin sidebar de WP.
 *
 * El home (10008) tiene su hero hardcodeado como widget HTML por su layout
 * complejo (h1 + lead + CTAs + facts en un solo bloque). Mantenemos el
 * shortcode [bcii_hero_image] ahí, que respeta la "Imagen Destacada" de la
 * página — editable desde Elementor (engranaje abajo izquierda → Page
 * Settings → Featured Image) o desde wp-admin → Pages.
 *
 * Idempotente. Re-ejecutable: si encuentra que la sección ya es widget Image,
 * solo actualiza el attachment_id si cambió.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/inject-hero.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────────────────────
 *  Mapping: post_id → attachment_id (Imagen hero por página)
 * ────────────────────────────────────────────────────────── */
$mapping = [
    10008 => 10051, // Home               → bcii-hero-token         (vía shortcode)
    10009 => 10051, // Token              → bcii-hero-token
    10010 => 10052, // Platform           → bcii-illustration-platform
    10011 => 10053, // Strategy/Advert.   → bcii-illustration-advertising
    10012 => 10054, // Business Model     → bcii-illustration-business
    10013 => 10055, // Market             → bcii-illustration-market
    10014 => 10056, // Investment Thesis  → bcii-illustration-investment
];

/* Páginas que reciben Widget Image (no aplica al home, que mantiene HTML+shortcode) */
$image_widget_pages = [ 10009, 10010, 10011, 10012, 10013, 10014 ];

function bcii_uid( $prefix = '' ) {
    return $prefix . substr( md5( uniqid( '', true ) . mt_rand() ), 0, 7 );
}

/**
 * Construye una sección Elementor con un único widget Image dentro.
 * La sección queda con CSS class .bcii-page-illustration y el widget con
 * .bcii-hero-media .reveal — replicando el render previo en HTML puro.
 */
function bcii_make_image_section( $aid ) {
    $url = wp_get_attachment_image_url( $aid, 'full' );
    if ( ! $url ) return null;

    $alt = trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) );
    if ( $alt === '' ) {
        $alt = get_the_title( $aid );
    }

    return [
        'id'       => bcii_uid(),
        'elType'   => 'section',
        'settings' => [
            'gap'              => 'no',
            'structure'        => '10',
            'content_position' => 'top',
            'padding'          => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ],
            '_css_classes'     => 'bcii-page-illustration',
        ],
        'elements' => [
            [
                'id'       => bcii_uid(),
                'elType'   => 'column',
                'settings' => [
                    '_column_size' => 100,
                    '_inline_size' => null,
                    'padding'      => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ],
                ],
                'elements' => [
                    [
                        'id'         => bcii_uid(),
                        'elType'     => 'widget',
                        'widgetType' => 'image',
                        'settings'   => [
                            'image'        => [
                                'id'     => (int) $aid,
                                'url'    => $url,
                                'alt'    => $alt,
                                'source' => 'library',
                            ],
                            'image_size'   => 'full',
                            'align'        => 'center',
                            '_css_classes' => 'bcii-hero-media reveal',
                        ],
                        'elements'   => [],
                    ],
                ],
                'isInner'  => false,
            ],
        ],
        'isInner'  => false,
    ];
}

/* ──────────────────────────────────────────────────────────
 * 1. Featured image en TODAS las páginas (sirve para el shortcode del home
 *    y como fallback general). Idempotente.
 * ────────────────────────────────────────────────────────── */
foreach ( $mapping as $pid => $aid ) {
    $current = (int) get_post_thumbnail_id( $pid );
    if ( $current !== (int) $aid ) {
        set_post_thumbnail( $pid, $aid );
        WP_CLI::success( "page {$pid} → featured image = {$aid}" );
    } else {
        WP_CLI::log( "[done] page {$pid} → featured image ya es {$aid}" );
    }
}

/* ──────────────────────────────────────────────────────────
 * 2. Páginas internas: convertir la sección bcii-page-illustration a
 *    Widget Image nativo de Elementor (si todavía es HTML widget).
 * ────────────────────────────────────────────────────────── */
foreach ( $image_widget_pages as $pid ) {
    $aid = $mapping[ $pid ] ?? 0;
    if ( ! $aid ) continue;

    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) {
        WP_CLI::warning( "[skip] page {$pid} — sin _elementor_data" );
        continue;
    }

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) {
        WP_CLI::warning( "[skip] page {$pid} — JSON inválido" );
        continue;
    }

    $touched = false;

    foreach ( $data as $sec_idx => $sec ) {
        // Detectar la sección actual de la banda hero — puede estar como widget HTML
        // (versión vieja) o como widget Image (esta misma versión, re-ejecución).
        $sec_json = wp_json_encode( $sec );
        $is_band  = $sec_json && strpos( $sec_json, 'bcii-page-illustration' ) !== false;
        if ( ! $is_band ) continue;

        // ¿Ya tiene widget Image? Solo actualizar el attachment_id si cambió.
        $existing_image_widget = null;
        if ( ! empty( $sec['elements'] ) ) {
            foreach ( $sec['elements'] as $col_idx => $col ) {
                if ( empty( $col['elements'] ) ) continue;
                foreach ( $col['elements'] as $w_idx => $w ) {
                    if ( ( $w['widgetType'] ?? '' ) === 'image' ) {
                        $existing_image_widget = [ $col_idx, $w_idx ];
                        break 2;
                    }
                }
            }
        }

        if ( $existing_image_widget ) {
            // Refrescar el id/url del attachment si difiere
            list( $col_idx, $w_idx ) = $existing_image_widget;
            $cur_id = (int) ( $data[ $sec_idx ]['elements'][ $col_idx ]['elements'][ $w_idx ]['settings']['image']['id'] ?? 0 );
            if ( $cur_id === (int) $aid ) {
                WP_CLI::log( "[done] page {$pid} → widget Image ya apunta a {$aid}" );
            } else {
                $url = wp_get_attachment_image_url( $aid, 'full' );
                $data[ $sec_idx ]['elements'][ $col_idx ]['elements'][ $w_idx ]['settings']['image']['id']  = (int) $aid;
                $data[ $sec_idx ]['elements'][ $col_idx ]['elements'][ $w_idx ]['settings']['image']['url'] = $url;
                $touched = true;
                WP_CLI::success( "page {$pid} → widget Image attachment_id {$cur_id} → {$aid}" );
            }
        } else {
            // Reemplazar la sección entera por la versión con widget Image
            $new_section = bcii_make_image_section( $aid );
            if ( $new_section ) {
                $data[ $sec_idx ] = $new_section;
                $touched = true;
                WP_CLI::success( "page {$pid} → sección convertida a widget Image (attachment {$aid})" );
            }
        }
        break; // solo procesamos la primera banda por página
    }

    if ( $touched ) {
        $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
        if ( $encoded === false ) {
            WP_CLI::warning( "[skip] page {$pid} — re-encode JSON falló" );
            continue;
        }
        update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
        delete_post_meta( $pid, '_elementor_css' );
    }
}

WP_CLI::log( 'Done.' );
