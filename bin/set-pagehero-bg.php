<?php
/**
 * BCII — Setea el background warm cream en la primera Elementor section
 * de cada página interna, para que el bg cubra detrás del page-hero text + image.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pages = array( 258, 10009, 10010, 10011, 10012, 10013, 10014, 10015, 10016, 10017 );
$color = '#f5eee4';

foreach ( $pages as $pid ) {
    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) continue;
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data[0] ) ) continue;

    $s = $data[0]['settings'] ?? array();
    if ( ( $s['background_color'] ?? '' ) === $color && ( $s['background_background'] ?? '' ) === 'classic' ) {
        WP_CLI::log( "[done] page {$pid} — section ya tiene bg warm" );
        continue;
    }
    $s['background_background'] = 'classic';
    $s['background_color']      = $color;
    $data[0]['settings'] = $s;

    update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES ) ) );
    delete_post_meta( $pid, '_elementor_css' );
    WP_CLI::success( "page {$pid} → section 0 background = {$color}" );
}
