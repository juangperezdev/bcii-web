<?php
/**
 * BCII — Reemplaza los símbolos de texto en los pillar cards y los números
 * grandes en los step-cards de "Cómo Funciona" por SVG icons custom.
 *
 * Idempotente: si detecta que ya hay <svg> en lugar del símbolo, skip.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/inject-svg-icons.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$home_id = 10008;
$home    = get_post_meta( $home_id, '_elementor_data', true );
if ( ! is_string( $home ) || $home === '' ) {
    WP_CLI::error( "no _elementor_data en {$home_id}" );
    return;
}

/* SVG icons (línea estilo Lucide, viewBox 24×24, stroke = currentColor) */
$svg_layers = '<svg viewBox=\"0 0 24 24\"><rect x=\"3\" y=\"4\" width=\"18\" height=\"4\" rx=\"1\"/><rect x=\"5\" y=\"10\" width=\"14\" height=\"4\" rx=\"1\"/><rect x=\"7\" y=\"16\" width=\"10\" height=\"4\" rx=\"1\"/><circle cx=\"12\" cy=\"6\" r=\"0.6\" fill=\"currentColor\"/></svg>';
$svg_bolt   = '<svg viewBox=\"0 0 24 24\"><path d=\"M13 2 4 14h7l-1 8 9-12h-7z\"/><path d=\"M19 5l-2 2M21 9h-2M19 13l-1-1\" opacity=\"0.5\"/></svg>';
$svg_shield = '<svg viewBox=\"0 0 24 24\"><path d=\"M12 3l8 3v6c0 4.5-3.4 8.5-8 9-4.6-.5-8-4.5-8-9V6z\"/><path d=\"M9 12l2 2 4-4\"/></svg>';

$svg_step1  = '<svg viewBox=\"0 0 24 24\"><path d=\"M4 21V8l6-4 6 4v13\"/><path d=\"M4 21h16\"/><path d=\"M9 21v-5h2v5\"/><path d=\"M16 12v-2M20 12v-2M16 16v-2M20 16v-2\"/><path d=\"M19 5l3-3M22 5h-3M22 2v3\"/></svg>';
$svg_step2  = '<svg viewBox=\"0 0 24 24\"><rect x=\"7\" y=\"3\" width=\"10\" height=\"18\" rx=\"2\"/><path d=\"M11 18h2\"/><circle cx=\"12\" cy=\"8\" r=\"1.5\"/><path d=\"M9 12c1-1 5-1 6 0\"/></svg>';
$svg_step3  = '<svg viewBox=\"0 0 24 24\"><circle cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M12 8v8M8 12h8\"/></svg>';
$svg_step4  = '<svg viewBox=\"0 0 24 24\"><path d=\"M3 8h15a3 3 0 0 1 3 3v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8z\"/><path d=\"M3 8V6a2 2 0 0 1 2-2h11l2 4\"/><circle cx=\"17\" cy=\"14\" r=\"1.2\" fill=\"currentColor\"/></svg>';

/* Mapping símbolo viejo → SVG nuevo (en su contexto pillar) */
$pillar_subs = array(
    /* "A New Asset Class"   → layered-stack icon */
    '<div class=\"vp-icon\">◆</div>'  => '<div class=\"vp-icon\">' . $svg_layers . '</div>',
    /* "Zero-Cost Advertising" → bolt icon */
    '<div class=\"vp-icon\">⚡</div>'  => '<div class=\"vp-icon\">' . $svg_bolt . '</div>',
    /* "Consumer Ownership" → shield-check */
    '<div class=\"vp-icon\">●</div>'  => '<div class=\"vp-icon\">' . $svg_shield . '</div>',
);

/* Step-cards: reemplazar <div class="step-n">N</div> por <div class="step-icon" data-step="N">SVG</div> */
$step_subs = array(
    '<div class=\"step-n\">1</div>' => '<div class=\"step-icon\" data-step=\"1\">' . $svg_step1 . '</div>',
    '<div class=\"step-n\">2</div>' => '<div class=\"step-icon\" data-step=\"2\">' . $svg_step2 . '</div>',
    '<div class=\"step-n\">3</div>' => '<div class=\"step-icon\" data-step=\"3\">' . $svg_step3 . '</div>',
    '<div class=\"step-n\">4</div>' => '<div class=\"step-icon\" data-step=\"4\">' . $svg_step4 . '</div>',
);

$new = $home;
$count = 0;

/* Idempotencia: si ya hay step-icon o el primer SVG layered, asume hecho */
if ( strpos( $new, 'step-icon' ) !== false && strpos( $new, $svg_layers ) !== false ) {
    WP_CLI::log( "[done] home {$home_id} — SVG icons ya inyectados" );
    return;
}

foreach ( $pillar_subs as $find => $replace ) {
    $c = substr_count( $new, $find );
    if ( $c > 0 ) {
        $new = str_replace( $find, $replace, $new );
        $count += $c;
        WP_CLI::log( "  · pillar {$find} reemplazado ({$c}x)" );
    }
}
foreach ( $step_subs as $find => $replace ) {
    $c = substr_count( $new, $find );
    if ( $c > 0 ) {
        $new = str_replace( $find, $replace, $new );
        $count += $c;
        WP_CLI::log( "  · step {$find} → step-icon ({$c}x)" );
    }
}

if ( $count === 0 ) {
    WP_CLI::warning( "home {$home_id} → ningún patrón matcheó (posible cambio de markup)" );
    return;
}

update_post_meta( $home_id, '_elementor_data', wp_slash( $new ) );
delete_post_meta( $home_id, '_elementor_css' );
WP_CLI::success( "home {$home_id} → {$count} reemplazos (3 pilares + 4 pasos = 7 esperado)" );
