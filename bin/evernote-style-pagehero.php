<?php
/**
 * BCII — Aplica estilo Evernote a las páginas internas:
 *   Section 0 (boxed):
 *     [HTML widget] page-hero text (tag, h1, lead, CTAs)
 *     [Image widget] bcii-hero-media reveal — imagen rounded, sombra, centrada
 *
 *  La sección antes (illustration) se elimina si existe.
 *
 * Idempotente.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/evernote-style-pagehero.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$mapping = array(
    258   => 10054, // About → business (team)
    10009 => 10051, // Token → hero-token
    10010 => 10052, // Platform → platform
    10011 => 10053, // Strategy → advertising
    10012 => 10054, // Business Model → business
    10013 => 10055, // Market → market
    10014 => 10056, // Investment → investment
    10015 => 10054, // Leadership → business (team)
    10016 => 10056, // News → investment (chart-heavy fits news)
    10017 => 10056, // Investor Relations → investment
);

$uid = function() { return substr( md5( uniqid( '', true ) . mt_rand() ), 0, 7 ); };

function bcii_make_image_widget_evernote( $aid, $uid_fn ) {
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
            '_css_classes' => 'bcii-hero-media reveal',   /* sin is-banner — usa default rounded+shadow */
        ),
        'elements' => array(),
    );
}

foreach ( $mapping as $pid => $aid ) {
    $current = (int) get_post_thumbnail_id( $pid );
    if ( $current !== (int) $aid ) {
        set_post_thumbnail( $pid, $aid );
    }

    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) continue;

    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data ) ) continue;

    /* 1) Localizar la sección con page-hero (HTML widget) */
    $hero_idx = -1;
    foreach ( $data as $i => $sec ) {
        $j = wp_json_encode( $sec );
        if ( $j && strpos( $j, 'page-hero' ) !== false ) {
            $hero_idx = $i;
            break;
        }
    }
    if ( $hero_idx === -1 ) {
        WP_CLI::warning( "[skip] page {$pid} — sin page-hero" );
        continue;
    }

    /* 2) Localizar (y luego eliminar) la sección illustration separada que pueda existir */
    $illu_idx = -1;
    $illu_image_widget = null;
    foreach ( $data as $i => $sec ) {
        if ( $i === $hero_idx ) continue;
        foreach ( ( $sec['elements'] ?? array() ) as $col ) {
            foreach ( ( $col['elements'] ?? array() ) as $w ) {
                if ( ( $w['widgetType'] ?? '' ) === 'image' &&
                     strpos( (string)( $w['settings']['_css_classes'] ?? '' ), 'bcii-hero-media' ) !== false ) {
                    $illu_idx = $i;
                    $illu_image_widget = $w;
                    break 3;
                }
            }
        }
    }

    /* 3) Verificar si ya está en estado Evernote (HTML primero, Image segundo, sin is-banner) */
    $hero_col   = $data[ $hero_idx ]['elements'][0] ?? null;
    $hero_widgets = $hero_col['elements'] ?? array();
    if ( count( $hero_widgets ) === 2 ) {
        $w0_type = $hero_widgets[0]['widgetType'] ?? '';
        $w1_type = $hero_widgets[1]['widgetType'] ?? '';
        $w1_cls  = $hero_widgets[1]['settings']['_css_classes'] ?? '';
        if ( $w0_type === 'html' && $w1_type === 'image' && strpos( $w1_cls, 'is-banner' ) === false ) {
            WP_CLI::log( "[done] page {$pid} — ya en estilo Evernote (text + image rounded)" );
            continue;
        }
    }

    /* 4) Construir el image widget — reutilizar si tenemos uno previo, o crear */
    $image_widget = $illu_image_widget;
    if ( ! $image_widget ) {
        $image_widget = bcii_make_image_widget_evernote( $aid, $uid );
        if ( ! $image_widget ) {
            WP_CLI::warning( "[skip] page {$pid} — no pude crear image widget" );
            continue;
        }
    }
    /* Limpiar is-banner y normalizar clases */
    $cls = (string)( $image_widget['settings']['_css_classes'] ?? '' );
    $cls = trim( str_replace( array( 'is-banner', '  ' ), array( '', ' ' ), $cls ) );
    if ( strpos( $cls, 'bcii-hero-media' ) === false ) $cls = 'bcii-hero-media';
    if ( strpos( $cls, 'reveal' ) === false ) $cls .= ' reveal';
    $image_widget['settings']['_css_classes'] = trim( $cls );
    /* RESPETAR el attachment_id existente si el usuario ya subió una imagen
     * propia desde Elementor — solo asignar el default si el widget está vacío
     * o apuntaba al attachment que mapeo automáticamente. */
    $existing_id = (int)( $image_widget['settings']['image']['id'] ?? 0 );
    if ( $existing_id === 0 ) {
        $image_widget['settings']['image'] = array(
            'id'     => (int) $aid,
            'url'    => wp_get_attachment_image_url( $aid, 'full' ),
            'alt'    => trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) ) ?: get_the_title( $aid ),
            'source' => 'library',
        );
    }
    $image_widget['settings']['image_size']   = 'full';
    if ( empty( $image_widget['id'] ) ) $image_widget['id'] = $uid();

    /* 5) Limpiar el HTML del page-hero: quitar is-with-banner si existe */
    $patched = array();
    foreach ( $hero_widgets as $w ) {
        if ( ( $w['widgetType'] ?? '' ) === 'html' ) {
            $h = $w['settings']['html'] ?? '';
            $h = str_replace( '<section class="page-hero is-with-banner"', '<section class="page-hero"', $h );
            $w['settings']['html'] = $h;
            $patched[] = $w;
        } elseif ( ( $w['widgetType'] ?? '' ) === 'image' ) {
            // skipear images viejas (las reemplazamos)
            continue;
        } else {
            $patched[] = $w;
        }
    }
    /* Agregar el image widget al final */
    $patched[] = $image_widget;
    $data[ $hero_idx ]['elements'][0]['elements'] = $patched;

    /* Section settings: padding default, NO full_width — el image widget tiene
     * su propio max-width 1080. */
    $sec_settings = $data[ $hero_idx ]['settings'] ?? array();
    unset( $sec_settings['layout'] );
    $sec_settings['padding'] = array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true );
    $data[ $hero_idx ]['settings'] = $sec_settings;

    /* 6) Eliminar la sección illustration si existía (después del hero idx) */
    if ( $illu_idx !== -1 && $illu_idx !== $hero_idx ) {
        array_splice( $data, $illu_idx, 1 );
    }

    $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
    update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
    /* Sin esto Elementor corre la migración 3.x→4.x al abrir el editor y
     * puede dejar el image widget en un estado inconsistente (no editable). */
    update_post_meta( $pid, '_elementor_version', ELEMENTOR_VERSION );
    delete_post_meta( $pid, '_elementor_css' );
    delete_post_meta( $pid, '_elementor_page_assets' );

    WP_CLI::success( "page {$pid} → estilo Evernote: text + image rounded debajo (sección {$illu_idx} eliminada)" );
}

WP_CLI::log( 'Done.' );
