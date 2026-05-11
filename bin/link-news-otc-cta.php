<?php
/**
 * BCII — Inserta un CTA "View live feed on OTC Markets" arriba de la lista
 * de press releases en /news/, sólo si no existe.
 *
 * Idempotente.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$news_id = 10016;
$news    = get_post_meta( $news_id, '_elementor_data', true );
if ( ! is_string( $news ) || $news === '' ) {
    WP_CLI::warning( "no _elementor_data en {$news_id}" );
    return;
}

if ( strpos( $news, 'data-news-otc-cta' ) !== false ) {
    WP_CLI::log( "[done] /news/ {$news_id} — CTA OTC ya presente" );
    return;
}

// Insertar el CTA justo después del label-row de "Press Releases".
$find = '<div class=\\"label-row\\"><div class=\\"section-label\\">Press Releases</div><div class=\\"badge badge-accent\\">2025\\u20132026</div></div>';
$cta  = '<a href=\\"https://www.otcmarkets.com/stock/BCII/news\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\" class=\\"btn btn-outline\\" data-news-otc-cta=\\"1\\" style=\\"margin-top:1.25rem;\\">View live feed on OTC Markets \\u2197</a>';

if ( strpos( $news, $find ) === false ) {
    // intentar variante con guion ASCII (-) en vez del en-dash unicode
    $find_alt = '<div class=\\"label-row\\"><div class=\\"section-label\\">Press Releases</div><div class=\\"badge badge-accent\\">2025\\u20132026</div></div>';
    if ( strpos( $news, $find_alt ) === false ) {
        // dump label-row variant para debug
        if ( preg_match( '~<div class=\\\\"label-row\\\\">[^<]*<div class=\\\\"section-label\\\\">Press Releases</div>[^<]*<div class=\\\\"badge badge-accent\\\\">[^<]+</div></div>~', $news, $m ) ) {
            $find = $m[0];
        } else {
            WP_CLI::warning( "[miss] no se encontró el label-row de Press Releases" );
            return;
        }
    } else {
        $find = $find_alt;
    }
}

$replace = $find . $cta;
$new     = str_replace( $find, $replace, $news );
update_post_meta( $news_id, '_elementor_data', wp_slash( $new ) );
delete_post_meta( $news_id, '_elementor_css' );
WP_CLI::success( "/news/ {$news_id} → CTA OTC insertado arriba de la lista" );
