<?php
/**
 * Execor Child Theme — functions.php
 *
 * Carga los estilos del tema padre (Execor) y del child theme.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -----------------------------------------------
   1. Encolar estilos: padre + hijo
----------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'execor_child_enqueue_styles', 20 );
function execor_child_enqueue_styles() {

    // Estilo del tema PADRE
    wp_enqueue_style(
        'execor-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // Estilo del CHILD theme (sobreescribe al padre)
    wp_enqueue_style(
        'execor-child-style',
        get_stylesheet_uri(),
        array( 'execor-parent-style' ),
        wp_get_theme()->get( 'Version' )
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
