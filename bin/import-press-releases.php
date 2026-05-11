<?php
/**
 * BCII — Importer de press releases desde JSON al CPT bcii_pr.
 *
 * Lee /var/www/html/seed/press-releases.json y crea/actualiza un post bcii_pr
 * por entrada. Es idempotente: usa el meta `bcii_pr_external_url` (o el `otc_news_id`)
 * como key para detectar duplicados — re-ejecutar no crea copias, solo actualiza
 * los campos.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/import-press-releases.php
 *
 * Para sumar más noticias, agregá entradas al JSON con la misma forma:
 *
 *   {
 *     "title": "Título",
 *     "body": "Cuerpo / resumen",
 *     "tag": "ACCOUNTING · FASB",
 *     "date_display": "February 20<br>2026",
 *     "date_iso": "2026-02-20 12:00:00",
 *     "external_url": "https://www.otcmarkets.com/stock/BCII/news/...",
 *     "otc_news_id": "511301"
 *   }
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$json_path = '/var/www/html/seed/press-releases.json';
if ( ! file_exists( $json_path ) ) {
    WP_CLI::error( "No existe: {$json_path}" );
    return;
}

$items = json_decode( file_get_contents( $json_path ), true );
if ( ! is_array( $items ) ) {
    WP_CLI::error( "JSON inválido en {$json_path}" );
    return;
}

WP_CLI::log( "Cargados " . count( $items ) . " items para importar." );

$created = 0;
$updated = 0;

foreach ( $items as $it ) {
    $title = (string) ( $it['title'] ?? '' );
    if ( $title === '' ) continue;

    $body         = (string) ( $it['body'] ?? '' );
    $tag          = (string) ( $it['tag'] ?? '' );
    $date_display = (string) ( $it['date_display'] ?? '' );
    $date_iso     = (string) ( $it['date_iso'] ?? '' );
    $external_url = (string) ( $it['external_url'] ?? '' );
    $otc_id       = (string) ( $it['otc_news_id'] ?? '' );

    /* Buscar existente por otc_news_id (más estable) o por título exacto */
    $existing_id = 0;

    if ( $otc_id !== '' ) {
        $q = new WP_Query( array(
            'post_type'   => 'bcii_pr',
            'post_status' => 'any',
            'meta_key'    => 'bcii_pr_otc_news_id',
            'meta_value'  => $otc_id,
            'fields'      => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ) );
        if ( $q->have_posts() ) $existing_id = (int) $q->posts[0];
    }
    if ( ! $existing_id ) {
        $existing = get_page_by_title( $title, OBJECT, 'bcii_pr' );
        if ( $existing ) $existing_id = (int) $existing->ID;
    }

    $postarr = array(
        'post_type'    => 'bcii_pr',
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_content' => $body,
    );
    if ( $date_iso !== '' ) {
        $postarr['post_date']     = $date_iso;
        $postarr['post_date_gmt'] = get_gmt_from_date( $date_iso );
    }

    if ( $existing_id ) {
        $postarr['ID'] = $existing_id;
        wp_update_post( $postarr );
        $updated++;
        $action = 'UPDATE';
    } else {
        $existing_id = wp_insert_post( $postarr );
        $created++;
        $action = 'CREATE';
    }

    update_post_meta( $existing_id, 'bcii_pr_external_url', $external_url );
    update_post_meta( $existing_id, 'bcii_pr_display_date', $date_display );
    update_post_meta( $existing_id, 'bcii_pr_tag',          $tag );
    if ( $otc_id !== '' ) update_post_meta( $existing_id, 'bcii_pr_otc_news_id', $otc_id );

    WP_CLI::log( sprintf( '  %s #%d  %s  %s', $action, $existing_id, substr( $date_iso, 0, 10 ), substr( $title, 0, 80 ) ) );
}

WP_CLI::success( "Done. Created: {$created} · Updated: {$updated}" );
