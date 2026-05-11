<?php
/**
 * BCII — Restructura el hero del home en widgets Elementor nativos.
 *
 * Antes: una sola Elementor section con un HTML widget gigante que contenía
 *        toda la estructura `<section class="bcii-hero">` (imagen + texto)
 *        — la imagen sólo era editable vía wp-admin → Imagen destacada.
 *
 * Después: dos widgets nativos dentro de la misma Elementor section:
 *
 *   Section 0 (Elementor):
 *     settings.layout = 'full_width'   (sin max-width interno → permite edge-to-edge)
 *     settings.padding = 0
 *
 *     Column 100%:
 *       Widget [0] = Image widget (NATIVO)
 *         · image.id  = 10051   (bcii-hero-token)
 *         · image_size = 'full'
 *         · _css_classes = 'bcii-hero-media is-banner reveal'
 *         → CLICKEABLE desde el editor Elementor: panel "Edit Image"
 *
 *       Widget [1] = HTML widget (texto del hero)
 *         · `<div class="bcii-hero">`-wrapper con h1, lead, btns, hero-facts
 *
 * Idempotente: si detecta que el primer widget de la primera sección ya es
 * un Image widget, no toca.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/restructure-home-hero.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home_id = 10008;
$raw     = get_post_meta( $home_id, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) {
    WP_CLI::error( "no _elementor_data en {$home_id}" );
    return;
}

$data = json_decode( $raw, true );
if ( ! is_array( $data ) || empty( $data[0] ) ) {
    WP_CLI::error( "JSON inválido o vacío" );
    return;
}

/* Idempotencia */
$first_widget = $data[0]['elements'][0]['elements'][0] ?? null;
if ( $first_widget && ( $first_widget['widgetType'] ?? '' ) === 'image' ) {
    WP_CLI::log( "[done] home {$home_id} — primer widget ya es Image (estructura nueva)" );
    return;
}

/* Extraer el HTML actual del primer widget para reutilizar el texto */
$old_html = $first_widget['settings']['html'] ?? '';
if ( strpos( $old_html, 'bcii-hero' ) === false ) {
    WP_CLI::error( "no encuentro <section class=bcii-hero> en el primer widget" );
    return;
}

/* Quitar el <div class="bcii-hero-media">…</div> del HTML actual y mantener
 * el resto (h1, lead, btns, hero-facts). Reemplazar el wrapper <section> por
 * un <div> ya que el section va a estar en el column de Elementor. */
$text_html = $old_html;
// Sacar bcii-hero-media (esté donde esté en el HTML)
$text_html = preg_replace( '~\s*<div class="bcii-hero-media[^"]*">\[bcii_hero_image\]</div>\s*~', "\n    ", $text_html );

/* Helper para IDs únicos */
$uid = function() { return substr( md5( uniqid( '', true ) . mt_rand() ), 0, 7 ); };

/* Image widget nativo */
$image_url = wp_get_attachment_image_url( 10051, 'full' );
$image_alt = trim( (string) get_post_meta( 10051, '_wp_attachment_image_alt', true ) );
if ( $image_alt === '' ) $image_alt = 'BCII Super Coupon Token concept';

$image_widget = array(
    'id'         => $uid(),
    'elType'     => 'widget',
    'widgetType' => 'image',
    'settings'   => array(
        'image' => array(
            'id'     => 10051,
            'url'    => $image_url,
            'alt'    => $image_alt,
            'source' => 'library',
        ),
        'image_size'   => 'full',
        'align'        => 'center',
        '_css_classes' => 'bcii-hero-media is-banner reveal',
    ),
    'elements' => array(),
);

/* HTML widget con el texto */
$text_widget = array(
    'id'         => $uid(),
    'elType'     => 'widget',
    'widgetType' => 'html',
    'settings'   => array( 'html' => $text_html ),
    'elements'   => array(),
);

/* Reconstruir la primera sección — mantener el ID original para no perder
 * referencias en el editor */
$orig_section = $data[0];
$orig_col     = $orig_section['elements'][0] ?? null;

$new_section = array(
    'id'       => $orig_section['id'] ?? $uid(),
    'elType'   => 'section',
    'settings' => array_merge(
        is_array( $orig_section['settings'] ?? null ) ? $orig_section['settings'] : array(),
        array(
            'layout'   => 'full_width',
            'gap'      => 'no',
            'structure'=> '10',
            'padding'  => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
        )
    ),
    'elements' => array(
        array(
            'id'       => $orig_col['id'] ?? $uid(),
            'elType'   => 'column',
            'settings' => array(
                '_column_size' => 100,
                '_inline_size' => null,
                'padding'      => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
            ),
            'elements' => array( $image_widget, $text_widget ),
            'isInner'  => false,
        ),
    ),
    'isInner'  => false,
);

$data[0] = $new_section;

$encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
update_post_meta( $home_id, '_elementor_data', wp_slash( $encoded ) );
delete_post_meta( $home_id, '_elementor_css' );

WP_CLI::success( "home {$home_id} → hero restructurado: Image widget (editable) + HTML widget (texto)" );
