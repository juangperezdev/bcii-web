<?php
/**
 * BCII — Mueve la imagen banner al ADENTRO de la primera sección (page-hero)
 * de cada página interna, en lugar de tenerla como sección 1 separada.
 *
 * Antes:
 *   Section[0] = HTML widget (page-hero text)
 *   Section[1] = Image widget (bcii-hero-media is-banner)   ← banda separada
 *
 * Después:
 *   Section[0] = layout:full_width, padding:0
 *     Column:
 *       Image widget (bcii-hero-media is-banner reveal)     ← imagen DENTRO
 *       HTML widget (page-hero text con clase is-with-banner)
 *   (la antigua Section[1] eliminada)
 *
 * Para About (258) que no tenía imagen, se le asigna featured image #10054
 * (bcii-illustration-business — temática de equipo, fits "About BCII") y
 * se construye la misma estructura.
 *
 * Idempotente. Re-ejecutable.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/merge-illustration-into-pagehero.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* Mapping post_id → attachment_id (por si la página no tiene featured image todavía) */
$mapping = array(
    258   => 10054, // About → bcii-illustration-business (team)
    10009 => 10051, // Token → bcii-hero-token
    10010 => 10052, // Platform → bcii-illustration-platform
    10011 => 10053, // Strategy → bcii-illustration-advertising
    10012 => 10054, // Business Model → bcii-illustration-business
    10013 => 10055, // Market → bcii-illustration-market
    10014 => 10056, // Investment → bcii-illustration-investment
);

$uid = function() { return substr( md5( uniqid( '', true ) . mt_rand() ), 0, 7 ); };

function bcii_make_image_widget( $aid, $uid_fn ) {
    $url = wp_get_attachment_image_url( $aid, 'full' );
    if ( ! $url ) return null;
    $alt = trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) );
    if ( $alt === '' ) $alt = get_the_title( $aid );
    return array(
        'id'         => $uid_fn(),
        'elType'     => 'widget',
        'widgetType' => 'image',
        'settings'   => array(
            'image' => array(
                'id'     => (int) $aid,
                'url'    => $url,
                'alt'    => $alt,
                'source' => 'library',
            ),
            'image_size'   => 'full',
            'align'        => 'center',
            '_css_classes' => 'bcii-hero-media is-banner reveal',
        ),
        'elements' => array(),
    );
}

foreach ( $mapping as $pid => $aid ) {
    /* 1) Asegurar featured image */
    $current = (int) get_post_thumbnail_id( $pid );
    if ( $current !== (int) $aid ) {
        set_post_thumbnail( $pid, $aid );
        WP_CLI::log( "  page {$pid} → featured image set to {$aid}" );
    }

    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) {
        WP_CLI::warning( "[skip] page {$pid} — sin _elementor_data" );
        continue;
    }
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data ) ) {
        WP_CLI::warning( "[skip] page {$pid} — JSON inválido" );
        continue;
    }

    /* Idempotencia: si la primera sección ya tiene 2 widgets (image + html con
     * is-with-banner), skip. */
    $first_col   = $data[0]['elements'][0] ?? null;
    $first_widgets = $first_col['elements'] ?? array();
    $already_merged = false;
    if ( count( $first_widgets ) >= 2 ) {
        $w0 = $first_widgets[0];
        $w1_html = $first_widgets[1]['settings']['html'] ?? '';
        if ( ( $w0['widgetType'] ?? '' ) === 'image' && strpos( $w1_html, 'is-with-banner' ) !== false ) {
            $already_merged = true;
        }
    }
    if ( $already_merged ) {
        WP_CLI::log( "[done] page {$pid} — page-hero ya tiene image + text mergeados" );
        continue;
    }

    /* 2) Localizar la sección con el page-hero (HTML widget) */
    $hero_idx = -1;
    foreach ( $data as $i => $sec ) {
        $j = wp_json_encode( $sec );
        if ( $j && strpos( $j, 'page-hero' ) !== false && strpos( $j, 'bcii-hero-media' ) === false ) {
            $hero_idx = $i;
            break;
        }
    }
    if ( $hero_idx === -1 ) {
        // Fallback: la primera sección
        $hero_idx = 0;
    }

    /* 3) Localizar la sección illustration (con Image widget bcii-hero-media) */
    $illu_idx = -1;
    $illu_image_widget = null;
    foreach ( $data as $i => $sec ) {
        if ( $i === $hero_idx ) continue;
        $cols = $sec['elements'] ?? array();
        foreach ( $cols as $col ) {
            foreach ( ( $col['elements'] ?? array() ) as $w ) {
                if ( ( $w['widgetType'] ?? '' ) === 'image' &&
                     strpos( (string) ( $w['settings']['_css_classes'] ?? '' ), 'bcii-hero-media' ) !== false ) {
                    $illu_idx = $i;
                    $illu_image_widget = $w;
                    break 3;
                }
            }
        }
    }

    /* 4) Conseguir el image widget — si la página no tenía illustration, crearlo */
    $image_widget = $illu_image_widget;
    if ( ! $image_widget ) {
        $image_widget = bcii_make_image_widget( $aid, $uid );
        if ( ! $image_widget ) {
            WP_CLI::warning( "[skip] page {$pid} — no pude crear image widget para attachment {$aid}" );
            continue;
        }
    }
    /* Forzar las clases banner + actualizar settings */
    $image_widget['settings']['_css_classes'] = 'bcii-hero-media is-banner reveal';
    $image_widget['settings']['image']        = array(
        'id'     => (int) $aid,
        'url'    => wp_get_attachment_image_url( $aid, 'full' ),
        'alt'    => trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) ) ?: get_the_title( $aid ),
        'source' => 'library',
    );
    $image_widget['settings']['image_size']   = 'full';
    if ( empty( $image_widget['id'] ) ) $image_widget['id'] = $uid();

    /* 5) Reconstruir la sección page-hero */
    $hero_section = $data[ $hero_idx ];
    $col_idx      = 0;
    $hero_col     = $hero_section['elements'][ $col_idx ] ?? null;
    if ( ! $hero_col ) {
        WP_CLI::warning( "[skip] page {$pid} — page-hero section sin column" );
        continue;
    }
    $existing_widgets = $hero_col['elements'] ?? array();

    /* Modificar el HTML widget del page-hero para que su <section class="page-hero"> tenga la clase modificadora is-with-banner */
    $patched_widgets = array();
    foreach ( $existing_widgets as $w ) {
        if ( ( $w['widgetType'] ?? '' ) === 'html' ) {
            $h = $w['settings']['html'] ?? '';
            if ( strpos( $h, 'page-hero' ) !== false && strpos( $h, 'is-with-banner' ) === false ) {
                $h = preg_replace( '~<section class="page-hero"~', '<section class="page-hero is-with-banner"', $h, 1 );
                $w['settings']['html'] = $h;
            }
        }
        $patched_widgets[] = $w;
    }

    /* Insertar el image widget como PRIMER widget de la columna */
    array_unshift( $patched_widgets, $image_widget );
    $hero_section['elements'][ $col_idx ]['elements'] = $patched_widgets;

    /* Section settings → full_width, padding 0 (la imagen va edge-to-edge,
     * el page-hero text trae su propio padding interno) */
    $hero_section['settings'] = array_merge(
        is_array( $hero_section['settings'] ?? null ) ? $hero_section['settings'] : array(),
        array(
            'layout'  => 'full_width',
            'padding' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
        )
    );

    $data[ $hero_idx ] = $hero_section;

    /* 6) Eliminar la antigua sección illustration (si existía) */
    if ( $illu_idx !== -1 && $illu_idx !== $hero_idx ) {
        // splice respeta los índices
        if ( $illu_idx > $hero_idx ) {
            array_splice( $data, $illu_idx, 1 );
        } else {
            array_splice( $data, $illu_idx, 1 );
        }
    }

    $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
    if ( $encoded === false ) {
        WP_CLI::warning( "[skip] page {$pid} — re-encode falló" );
        continue;
    }

    update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
    delete_post_meta( $pid, '_elementor_css' );

    WP_CLI::success( sprintf( 'page %d → image (att %d) DENTRO del page-hero %s',
        $pid, $aid, $illu_idx !== -1 ? "(sección {$illu_idx} eliminada)" : '(creada)' ) );
}

WP_CLI::log( 'Done.' );
