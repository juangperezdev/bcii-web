<?php
/**
 * BCII — Inyecta media en Elementor data:
 *  - Home (10008): hero-media DENTRO del .bcii-hero (vía str_replace).
 *  - Páginas internas: agrega una sección Elementor extra con una banda
 *    .bcii-page-illustration <img> después del page-hero (índice 1 del array).
 *
 * Idempotente: si el marker BCII_ILLUSTRATION ya está, no duplica.
 *
 * Correr: docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/inject-hero.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$marker = 'bcii-page-illustration';

/* ──────────────────────────────────────────────────────────
 * 1. Home — hero image dentro del bloque .bcii-hero
 * ────────────────────────────────────────────────────────── */
$home_id     = 10008;
$home_find   = '</div>\n    <div class=\"hero-facts\">';
$home_replace = '</div>\n    <div class=\"bcii-hero-media reveal\"><img src=\"/wp-content/uploads/2026/05/bcii-hero-token.jpg\" alt=\"BCII Super Coupon Token concept\" loading=\"eager\" /></div>\n    <div class=\"hero-facts\">';
$home_meta   = get_post_meta( $home_id, '_elementor_data', true );

if ( is_string( $home_meta ) && $home_meta !== '' ) {
    if ( strpos( $home_meta, 'bcii-hero-media' ) !== false ) {
        WP_CLI::log( "[done] home {$home_id} — hero-media ya inyectado" );
    } elseif ( strpos( $home_meta, $home_find ) !== false ) {
        $new = str_replace( $home_find, $home_replace, $home_meta );
        update_post_meta( $home_id, '_elementor_data', wp_slash( $new ) );
        delete_post_meta( $home_id, '_elementor_css' );
        WP_CLI::success( "home {$home_id} → hero image inyectado" );
    } else {
        WP_CLI::warning( "[miss] home {$home_id} — patrón hero-facts no encontrado" );
    }
}

/* ──────────────────────────────────────────────────────────
 * 2. Páginas internas — sección illustration debajo del page-hero
 * ────────────────────────────────────────────────────────── */
$pages = [
    10009 => [ '/wp-content/uploads/2026/05/bcii-hero-token.jpg',          'How the Super Coupon Token works — digital token concept' ],
    10010 => [ '/wp-content/uploads/2026/05/bcii-illustration-platform.jpg', 'BCII Platform infrastructure' ],
    10011 => [ '/wp-content/uploads/2026/05/bcii-illustration-advertising.jpg', 'Advertising disruption strategy' ],
    10012 => [ '/wp-content/uploads/2026/05/bcii-illustration-business.jpg', 'Business model and revenue streams' ],
    10013 => [ '/wp-content/uploads/2026/05/bcii-illustration-market.jpg',  'Market opportunity and industry context' ],
    10014 => [ '/wp-content/uploads/2026/05/bcii-illustration-investment.jpg', 'Investment thesis charts' ],
];

function bcii_make_illustration_section( $url, $alt ) {
    $url_e  = esc_url( $url );
    $alt_e  = esc_attr( $alt );
    $html   = '<section class="bcii-page-illustration"><div class="container"><div class="bcii-hero-media reveal"><img src="' . $url_e . '" alt="' . $alt_e . '" loading="lazy" /></div></div></section>';
    return [
        'id'       => substr( md5( $url . microtime( true ) ), 0, 7 ),
        'elType'   => 'section',
        'settings' => (object) [
            'gap'              => 'no',
            'structure'        => '10',
            'content_position' => 'top',
            'padding'          => (object) [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ],
        ],
        'elements' => [
            [
                'id'       => substr( md5( $url . 'col' . microtime( true ) ), 0, 7 ),
                'elType'   => 'column',
                'settings' => (object) [
                    '_column_size' => 100,
                    '_inline_size' => null,
                    'padding'      => (object) [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ],
                ],
                'elements' => [
                    [
                        'id'         => substr( md5( $url . 'wid' . microtime( true ) ), 0, 7 ),
                        'elType'     => 'widget',
                        'settings'   => (object) [ 'html' => $html ],
                        'elements'   => [],
                        'widgetType' => 'html',
                    ],
                ],
                'isInner'  => false,
            ],
        ],
        'isInner'  => false,
    ];
}

foreach ( $pages as $pid => $info ) {
    list( $url, $alt ) = $info;

    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) {
        WP_CLI::warning( "[skip] page {$pid} — sin _elementor_data" );
        continue;
    }

    if ( strpos( $raw, 'bcii-page-illustration' ) !== false ) {
        WP_CLI::log( "[done] page {$pid} — illustration ya inyectada" );
        continue;
    }

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) {
        WP_CLI::warning( "[skip] page {$pid} — JSON inválido en _elementor_data" );
        continue;
    }

    // Localizar la sección que contiene .page-hero (la primera que matchee)
    $hero_idx = -1;
    foreach ( $data as $i => $sec ) {
        $section_json = wp_json_encode( $sec );
        if ( $section_json && strpos( $section_json, 'page-hero' ) !== false ) {
            $hero_idx = $i;
            break;
        }
    }
    if ( $hero_idx === -1 ) {
        WP_CLI::warning( "[miss] page {$pid} — no encontré la sección page-hero" );
        continue;
    }

    $new_section = bcii_make_illustration_section( $url, $alt );
    array_splice( $data, $hero_idx + 1, 0, [ $new_section ] );

    $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
    if ( $encoded === false ) {
        WP_CLI::warning( "[skip] page {$pid} — re-encode falló" );
        continue;
    }

    update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
    delete_post_meta( $pid, '_elementor_css' );

    WP_CLI::success( "page {$pid} → illustration inyectada (idx {$hero_idx} + 1)" );
}

WP_CLI::log( 'Done.' );
