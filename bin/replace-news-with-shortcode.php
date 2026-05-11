<?php
/**
 * BCII — Reemplaza el HTML hardcoded de news/press-releases por el shortcode
 * [bcii_press_releases].
 *
 *  - /news/ (10016): la sección Press Releases con 7 <div class="pr-item">
 *    se reemplaza por el shortcode con layout=list. El shortcode recorre
 *    todas las entradas del CPT bcii_pr y respeta su URL externa por item.
 *
 *  - Home (10008): el grid-3 de 3 news-cards se reemplaza por el shortcode
 *    con layout=cards, limit=3.
 *
 * Idempotente: detecta si el shortcode ya está y no toca.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* En el meta `_elementor_data` los atributos HTML están JSON-escapados:
 *   class="X"  →  class=\"X\"   (un backslash + comilla)
 * Para matchear ese `\"` en regex PHP necesitamos `\\\"` (3 chars: backslash
 * literal + quote escapado) o equivalente. Definimos un alias para legibilidad. */
$BSQ = '\\\\\\"';   // PHP single-quote: 6 chars source → 3 chars valor → regex matches \"

/* ────── /news/ ────── */
$news_id = 10016;
$news    = get_post_meta( $news_id, '_elementor_data', true );
if ( is_string( $news ) && strpos( $news, '[bcii_press_releases' ) === false ) {
    /* El container de pr-items empieza en
     *   <div style="display:flex; flex-direction:column; gap:1px; ...border:1px solid var(--border);">
     * y termina con su </div> de cierre. Después viene </div> del .container y </section>.
     * Pattern: matchea el container completo (con todos los pr-item adentro). */
    $pattern = '/<div style=' . $BSQ . 'display:flex; flex-direction:column; gap:1px; margin-top:2\.5rem; border:1px solid var\(--border\);' . $BSQ . '>.*?<\/div>\\\\n    <\/div>/s';
    $count   = 0;
    $new = preg_replace_callback( $pattern, function( $m ) use ( &$count ) {
        $count++;
        return '[bcii_press_releases limit=\\"50\\" layout=\\"list\\"]\\n    </div>';
    }, $news, 1 );

    if ( $count > 0 && $new !== null ) {
        update_post_meta( $news_id, '_elementor_data', wp_slash( $new ) );
        delete_post_meta( $news_id, '_elementor_css' );
        WP_CLI::success( "/news/ {$news_id} → reemplazado HTML hardcoded por shortcode (list)" );
    } else {
        WP_CLI::warning( "[miss] /news/ {$news_id} — patrón container no encontrado" );
    }
} else {
    WP_CLI::log( "[done] /news/ {$news_id} — shortcode ya presente" );
}

/* ────── Home (3 cards) ────── */
$home_id = 10008;
$home    = get_post_meta( $home_id, '_elementor_data', true );
if ( is_string( $home ) && strpos( $home, '[bcii_press_releases' ) === false ) {
    /* Matchea grid-3 con 2+ cards consecutivas. */
    $pattern_home = '/<div class=' . $BSQ . 'grid-3' . $BSQ . '>(?:\\\\n\s*)?(?:<a [^>]*class=' . $BSQ . 'news-card' . $BSQ . '[^>]*>.*?<\/a>\s*(?:\\\\n\s*)?){2,}<\/div>/s';
    $count_h = 0;
    $new_h = preg_replace_callback( $pattern_home, function( $m ) use ( &$count_h ) {
        $count_h++;
        return '[bcii_press_releases limit=\\"3\\" layout=\\"cards\\"]';
    }, $home, 1 );

    if ( $count_h > 0 && $new_h !== null ) {
        update_post_meta( $home_id, '_elementor_data', wp_slash( $new_h ) );
        delete_post_meta( $home_id, '_elementor_css' );
        WP_CLI::success( "home {$home_id} → news-cards reemplazadas por shortcode (cards limit=3)" );
    } else {
        WP_CLI::warning( "[miss] home {$home_id} — grid-3 con news-cards no encontrado" );
    }
} else {
    WP_CLI::log( "[done] home {$home_id} — shortcode ya presente" );
}
