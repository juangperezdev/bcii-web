<?php
/**
 * Execor Child Theme — functions.php
 *
 * Carga los estilos del tema padre (Execor) y del child theme.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -----------------------------------------------
   1. Encolar estilos: padre + hijo + BCII design + Google Fonts
----------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'execor_child_enqueue_styles', 20 );
function execor_child_enqueue_styles() {

    $child_dir = get_stylesheet_directory();
    $child_uri = get_stylesheet_directory_uri();
    $version   = wp_get_theme()->get( 'Version' );

    // Estilo del tema PADRE
    wp_enqueue_style(
        'execor-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // Estilo del CHILD theme (header del child theme — sobreescribe al padre)
    wp_enqueue_style(
        'execor-child-style',
        get_stylesheet_uri(),
        array( 'execor-parent-style' ),
        $version
    );

    // Google Fonts: Inter (única familia, según briefing Joe Salvani)
    wp_enqueue_style(
        'bcii-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );

    // Hoja de estilos de diseño BCII
    $design_path = $child_dir . '/assets/css/bcii-design.css';
    wp_enqueue_style(
        'bcii-design',
        $child_uri . '/assets/css/bcii-design.css',
        array( 'execor-child-style', 'bcii-google-fonts' ),
        file_exists( $design_path ) ? filemtime( $design_path ) : $version
    );
}

/* -----------------------------------------------
   1b. Preconnect a Google Fonts (mejora LCP)
----------------------------------------------- */
add_filter( 'wp_resource_hints', 'execor_child_resource_hints', 10, 2 );
function execor_child_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array( 'href' => 'https://fonts.googleapis.com' );
        $urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
    }
    return $urls;
}


/* -----------------------------------------------
   1b2. Dequeue stylesheets Vamtam que pisan element selectors
   (section, body, etc.) y rompen el design BCII en frontend público.
----------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'execor_child_dequeue_vamtam', 9999 );
function execor_child_dequeue_vamtam() {
    if ( is_admin() ) return;
    foreach ( array(
        'vamtam-front-all',
        'vamtam-theme-elementor-max',
        'vamtam-theme-elementor-below-max',
        'vamtam-theme-elementor-small',
    ) as $h ) {
        wp_dequeue_style( $h );
        wp_deregister_style( $h );
    }
}

/* -----------------------------------------------
   1c. Quitar wrappers Vamtam que rompen layouts full-bleed
   El parent envuelve cada bloque Gutenberg en .limit-wrapper
   (max-width pequeño) — incompatible con nuestras secciones BCII.
----------------------------------------------- */
add_action( 'init', 'execor_child_remove_vamtam_wrappers', 20 );
function execor_child_remove_vamtam_wrappers() {
    if ( function_exists( 'vamtam_render_block_add_wrapper' ) ) {
        remove_filter( 'render_block', 'vamtam_render_block_add_wrapper', 100 );
    }
    if ( function_exists( 'vamtam_remove_block_wrappers' ) ) {
        remove_filter( 'the_content', 'vamtam_remove_block_wrappers' );
    }
}

/* -----------------------------------------------
   1d. JS del nav (mobile toggle)
----------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'execor_child_enqueue_scripts', 20 );
function execor_child_enqueue_scripts() {
    $child_dir = get_stylesheet_directory();
    $child_uri = get_stylesheet_directory_uri();
    $js_path   = $child_dir . '/assets/js/nav.js';

    wp_enqueue_script(
        'bcii-nav',
        $child_uri . '/assets/js/nav.js',
        array(),
        file_exists( $js_path ) ? filemtime( $js_path ) : '1.0.0',
        true
    );
}

/* -----------------------------------------------
   2. Soporte adicional del tema
----------------------------------------------- */
add_action( 'after_setup_theme', 'execor_child_setup' );
function execor_child_setup() {

    // Miniaturas de posts
    add_theme_support( 'post-thumbnails' );

    // Título dinámico en el <head>
    add_theme_support( 'title-tag' );

    // HTML5 en elementos del core
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Menús de navegación personalizados
    register_nav_menus( array(
        'primary'   => __( 'Menú Principal', 'execor-child' ),
        'footer'    => __( 'Menú Footer',    'execor-child' ),
    ) );
}

/* -----------------------------------------------
   3. Registrar sidebars / widgets
----------------------------------------------- */
add_action( 'widgets_init', 'execor_child_widgets_init' );
function execor_child_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar Principal', 'execor-child' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Arrastrá widgets aquí.', 'execor-child' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
