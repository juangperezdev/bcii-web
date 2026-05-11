<?php
/**
 * BCII — Linkea las noticias a la página de OTC Markets en pestaña nueva.
 *
 * El backend de OTC Markets bloquea fetch desde server (WAF/CDN), así que en
 * lugar de auto-importar, hacemos que cada item de news del sitio abra el
 * listado oficial de BCII en otcmarkets.com — la fuente autorizada.
 *
 * Cambios:
 *  - Home (10008): cada <a class="news-card"> apunta a OTC, target _blank.
 *  - /news/ (10016): cada <div class="pr-item"> recibe un <a class="pr-link-stretched">
 *    envolviendo su título — convierte la card entera en un link clickeable
 *    (técnica "stretched link" + position:relative en .pr-item).
 *
 * Idempotente. Re-ejecutable: si detecta el href de OTC ya presente, no duplica.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/link-news-otc.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const BCII_OTC_NEWS_URL = 'https://www.otcmarkets.com/stock/BCII/news';

/* ──────────────────────────────────────────────────────────
 * 1. Home (10008): news-cards pointing a /news/ → OTC en blanco
 * ────────────────────────────────────────────────────────── */
$home_id = 10008;
$home    = get_post_meta( $home_id, '_elementor_data', true );

if ( is_string( $home ) && $home !== '' ) {
    if ( strpos( $home, 'otcmarkets.com/stock/BCII/news\\"' ) !== false || strpos( $home, 'otcmarkets.com/stock/BCII/news"' ) !== false ) {
        WP_CLI::log( "[done] home {$home_id} — news-cards ya apuntan a OTC" );
    } else {
        // <a href=\"/news/\" class=\"news-card\">
        //   →
        // <a href=\"<OTC>\" class=\"news-card\" target=\"_blank\" rel=\"noopener noreferrer\">
        $find    = '<a href=\\"/news/\\" class=\\"news-card\\">';
        $replace = '<a href=\\"' . BCII_OTC_NEWS_URL . '\\" class=\\"news-card\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\">';
        $count   = substr_count( $home, $find );
        if ( $count > 0 ) {
            $new = str_replace( $find, $replace, $home );
            update_post_meta( $home_id, '_elementor_data', wp_slash( $new ) );
            delete_post_meta( $home_id, '_elementor_css' );
            WP_CLI::success( "home {$home_id} → {$count} news-card(s) ahora apuntan a OTC en pestaña nueva" );
        } else {
            WP_CLI::warning( "[miss] home {$home_id} — no encontré news-cards <a href=\"/news/\">" );
        }
    }
}

/* ──────────────────────────────────────────────────────────
 * 2. /news/ (10016): wrappear pr-title con <a class="pr-link-stretched">
 *    para hacer cada pr-item clickeable hacia OTC.
 * ────────────────────────────────────────────────────────── */
$news_id = 10016;
$news    = get_post_meta( $news_id, '_elementor_data', true );

if ( is_string( $news ) && $news !== '' ) {
    if ( strpos( $news, 'pr-link-stretched' ) !== false ) {
        WP_CLI::log( "[done] /news/ {$news_id} — pr-items ya tienen pr-link-stretched" );
    } else {
        // Buscar:   <div class=\"pr-title\">TITLE</div>
        // Reemplazo: <div class=\"pr-title\"><a class=\"pr-link-stretched\" href=\"OTC\" target=\"_blank\" rel=\"noopener noreferrer\">TITLE</a></div>
        $url = BCII_OTC_NEWS_URL;
        $pattern = '~<div class=\\\\"pr-title\\\\">([^<]+)</div>~';

        $count = 0;
        $new   = preg_replace_callback( $pattern, function( $m ) use ( $url, &$count ) {
            $count++;
            return '<div class=\\"pr-title\\"><a class=\\"pr-link-stretched\\" href=\\"' . $url . '\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\">' . $m[1] . '</a></div>';
        }, $news );

        if ( $new !== null && $count > 0 ) {
            update_post_meta( $news_id, '_elementor_data', wp_slash( $new ) );
            delete_post_meta( $news_id, '_elementor_css' );
            WP_CLI::success( "/news/ {$news_id} → {$count} pr-title(s) envuelto(s) en stretched-link a OTC" );
        } else {
            WP_CLI::warning( "[miss] /news/ {$news_id} — no encontré .pr-title para envolver" );
        }
    }
}

WP_CLI::log( 'Done.' );
