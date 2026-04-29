<?php
/**
 * BCII — Child theme header
 *
 * Custom override del header de Execor. Diseño 1:1 con el mockup
 * (bcii-design.css → nav.bcii-nav). Mantenemos wp_head() y wp_body_open()
 * para que plugins (Elementor, Akismet) funcionen normalmente, pero
 * descartamos los wrappers Vamtam que no necesitamos.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$logo_id     = 10001; // bcii-logo-teal
$logo_url    = wp_get_attachment_image_url( $logo_id, 'full' );
$ir_url      = home_url( '/investor-relations/' );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'bcii' ); ?>>
<?php wp_body_open(); ?>

<nav class="bcii-nav">
    <div class="nav-inner">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-logo" aria-label="<?php bloginfo( 'name' ); ?>">
            <?php if ( $logo_url ) : ?>
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="BCII Enterprises">
            <?php else : ?>
                <span class="nav-logo-text">
                    <span class="brand">BCII</span>
                    <span class="sub">ENTERPRISES</span>
                </span>
            <?php endif; ?>
        </a>

        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'nav-links',
            'menu_id'        => 'navLinks',
            'fallback_cb'    => false,
            'depth'          => 1,
        ) );
        ?>

        <span class="nav-ticker" aria-label="OTC Pink ticker BCII">
            <span class="nav-ticker-dot"></span>
            OTC PINK: BCII
        </span>

        <a href="<?php echo esc_url( $ir_url ); ?>" class="nav-ir">Investor Relations</a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navLinks">☰</button>
    </div>
</nav>

<main id="main" class="bcii-main">
