<?php
/**
 * BCII — Mueve el <div class="bcii-hero-media"> del home para que sea hijo
 * directo del <section class="bcii-hero">, en lugar de estar dentro del
 * <div class="container">.
 *
 * Antes:
 *   <section class="bcii-hero">
 *     <div class="container">
 *       <span class="hero-tag">…</span>
 *       <h1>…</h1>
 *       <p class="lead">…</p>
 *       <div class="btns">…</div>
 *       <div class="bcii-hero-media reveal">[bcii_hero_image]</div>   ← AQUÍ
 *       <div class="hero-facts">…</div>
 *     </div>
 *   </section>
 *
 * Después:
 *   <section class="bcii-hero">
 *     <div class="bcii-hero-media reveal">[bcii_hero_image]</div>      ← MOVIDO ARRIBA
 *     <div class="container">
 *       <span class="hero-tag">…</span>
 *       <h1>…</h1>
 *       <p class="lead">…</p>
 *       <div class="btns">…</div>
 *       <div class="hero-facts">…</div>
 *     </div>
 *   </section>
 *
 * Esto le permite al CSS hacer position: absolute; inset: 0 y cubrir el
 * viewport entero (full-bleed). Idempotente.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/move-hero-media-fullbleed.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$home_id = 10008;
$home    = get_post_meta( $home_id, '_elementor_data', true );
if ( ! is_string( $home ) || $home === '' ) {
    WP_CLI::error( "no _elementor_data en {$home_id}" );
    return;
}

/* Idempotencia: si ya está fuera del container (justo después del <section>), skip */
$already = '<section class=\"bcii-hero\">\n  <div class=\"bcii-hero-media';
if ( strpos( $home, $already ) !== false ) {
    WP_CLI::log( "[done] home {$home_id} — bcii-hero-media ya es hijo directo del section" );
    return;
}

/* 1) Sacar el div del lugar viejo (justo antes de hero-facts) */
$old_block = '</div>\n    <div class=\"bcii-hero-media reveal\">[bcii_hero_image]</div>\n    <div class=\"hero-facts\">';
$new_block = '</div>\n    <div class=\"hero-facts\">';

if ( strpos( $home, $old_block ) === false ) {
    WP_CLI::warning( "[miss] home {$home_id} — no encontré el <div bcii-hero-media> en su posición esperada (antes de hero-facts)" );
    return;
}
$intermediate = str_replace( $old_block, $new_block, $home );

/* 2) Insertarlo antes del <div class="container"> dentro del .bcii-hero */
$section_open = '<section class=\"bcii-hero\">\n  <div class=\"container\">';
$section_with_media = '<section class=\"bcii-hero\">\n  <div class=\"bcii-hero-media reveal\">[bcii_hero_image]</div>\n  <div class=\"container\">';

if ( strpos( $intermediate, $section_open ) === false ) {
    WP_CLI::warning( "[miss] home {$home_id} — no encontré <section class=\"bcii-hero\"> + container" );
    return;
}
$final = str_replace( $section_open, $section_with_media, $intermediate );

update_post_meta( $home_id, '_elementor_data', wp_slash( $final ) );
delete_post_meta( $home_id, '_elementor_css' );
WP_CLI::success( "home {$home_id} → bcii-hero-media movido a hijo directo de .bcii-hero (full-bleed listo)" );
