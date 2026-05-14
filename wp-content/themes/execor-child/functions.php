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
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap',
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
   1b3. Dequeue Google Fonts NO usadas que inyecta el Vamtam Elementor Kit:
   DM Sans, Forum, Nothing You Could Do — la guía de diseño es Inter only.
   Cada font google es ~5-10 woff2 + un CSS render-blocking en <head>.
   El Kit registra las fonts en wp_print_styles, así que disparamos tarde.
----------------------------------------------- */
add_action( 'wp_print_styles', 'bcii_dequeue_unused_google_fonts', 100 );
function bcii_dequeue_unused_google_fonts() {
    if ( is_admin() ) return;
    foreach ( array(
        'elementor-gf-dmsans',
        'elementor-gf-forum',
        'elementor-gf-nothingyoucoulddo',
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
   2b. Shortcode [bcii_hero_image]
   ------------------------------------------------
   Renderiza la "Imagen destacada" (featured image) del post actual.
   Se usa dentro del HTML del hero / banda hero para que el cliente cambie
   la imagen desde el sidebar del editor de la página, sin tocar HTML.

   Uso:  [bcii_hero_image]                      → tamaño full
         [bcii_hero_image size="large"]         → tamaño WP grande (1024)
         [bcii_hero_image class="custom-cls"]   → clase extra en <img>

   Si la página no tiene imagen destacada, no devuelve nada (silent fallback).
----------------------------------------------- */
add_shortcode( 'bcii_hero_image', 'bcii_shortcode_hero_image' );
function bcii_shortcode_hero_image( $atts ) {
    if ( ! has_post_thumbnail() ) return '';

    $atts = shortcode_atts( array(
        'size'  => 'full',
        'class' => '',
    ), $atts, 'bcii_hero_image' );

    $tid = get_post_thumbnail_id();
    $url = wp_get_attachment_image_url( $tid, $atts['size'] );
    if ( ! $url ) return '';

    $alt = trim( (string) get_post_meta( $tid, '_wp_attachment_image_alt', true ) );
    if ( $alt === '' ) $alt = get_the_title();

    return sprintf(
        '<img src="%s" alt="%s" loading="lazy"%s />',
        esc_url( $url ),
        esc_attr( $alt ),
        $atts['class'] !== '' ? ' class="' . esc_attr( $atts['class'] ) . '"' : ''
    );
}

/* -----------------------------------------------
   2c. Permitir shortcodes dentro del Elementor HTML widget
   ------------------------------------------------
   Por defecto el widget HTML devuelve el contenido literal (no procesa
   shortcodes). Lo activamos para que [bcii_hero_image] funcione dentro
   del page-hero, banda hero, etc. sin migrar a otro widget.
----------------------------------------------- */
add_filter( 'elementor/widget/render_content', 'bcii_run_shortcodes_in_html_widget', 10, 2 );
function bcii_run_shortcodes_in_html_widget( $content, $widget ) {
    if ( $widget && method_exists( $widget, 'get_name' ) && $widget->get_name() === 'html' ) {
        return do_shortcode( $content );
    }
    return $content;
}

/* -----------------------------------------------
   2d. Custom Post Type: Press Release (bcii_pr)
   ------------------------------------------------
   Cada press release / noticia es un post propio editable desde
   wp-admin → "Press Releases". Tiene los meta:
      bcii_pr_external_url   — URL específica (OTC Markets u otra) que
                               abre cada card en pestaña nueva.
      bcii_pr_display_date   — texto libre para la fecha (ej. "February 20<br>2026")
      bcii_pr_tag            — categoría/tag (ej. "ACCOUNTING · FASB ASU 2023-08")

   El cuerpo (post_content) es el body resumen que se muestra en la card.
----------------------------------------------- */
add_action( 'init', 'bcii_register_press_release_cpt' );
function bcii_register_press_release_cpt() {
    register_post_type( 'bcii_pr', array(
        'labels' => array(
            'name'                  => 'Press Releases',
            'singular_name'         => 'Press Release',
            'menu_name'             => 'Press Releases',
            'add_new'               => 'Nuevo Press Release',
            'add_new_item'          => 'Agregar Press Release',
            'edit_item'             => 'Editar Press Release',
            'view_item'             => 'Ver Press Release',
            'search_items'          => 'Buscar Press Releases',
            'not_found'             => 'No hay press releases',
            'all_items'             => 'Todos los Press Releases',
        ),
        'public'              => false,            // No tiene URL pública propia (se linkea a OTC)
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,             // editable desde Gutenberg
        'menu_position'       => 22,
        'menu_icon'           => 'dashicons-megaphone',
        'supports'            => array( 'title', 'editor', 'custom-fields' ),
        'has_archive'         => false,
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'rewrite'             => false,
    ) );

    foreach ( array( 'bcii_pr_external_url', 'bcii_pr_display_date', 'bcii_pr_tag' ) as $key ) {
        register_post_meta( 'bcii_pr', $key, array(
            'show_in_rest' => true,
            'single'       => true,
            'type'         => 'string',
            'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
        ) );
    }
}

/* Meta box "Press Release Details" en el editor classic + Gutenberg sidebar */
add_action( 'add_meta_boxes', 'bcii_pr_meta_box' );
function bcii_pr_meta_box() {
    add_meta_box( 'bcii_pr_details', 'Press Release Details', 'bcii_pr_meta_box_render', 'bcii_pr', 'side', 'default' );
}
function bcii_pr_meta_box_render( $post ) {
    wp_nonce_field( 'bcii_pr_save', 'bcii_pr_nonce' );
    $url  = get_post_meta( $post->ID, 'bcii_pr_external_url', true );
    $date = get_post_meta( $post->ID, 'bcii_pr_display_date', true );
    $tag  = get_post_meta( $post->ID, 'bcii_pr_tag', true );
    ?>
    <p>
        <label for="bcii_pr_external_url" style="font-weight:600;display:block;margin-bottom:4px;">External URL <span style="font-weight:400;color:#888;">(target=_blank)</span></label>
        <input type="url" id="bcii_pr_external_url" name="bcii_pr_external_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://www.otcmarkets.com/stock/BCII/news/..." style="width:100%;" />
    </p>
    <p>
        <label for="bcii_pr_display_date" style="font-weight:600;display:block;margin-bottom:4px;">Display Date</label>
        <input type="text" id="bcii_pr_display_date" name="bcii_pr_display_date" value="<?php echo esc_attr( $date ); ?>" placeholder="February 20<br>2026" style="width:100%;" />
        <span style="font-size:11px;color:#888;">Permite &lt;br&gt; para salto de línea.</span>
    </p>
    <p>
        <label for="bcii_pr_tag" style="font-weight:600;display:block;margin-bottom:4px;">Category Tag</label>
        <input type="text" id="bcii_pr_tag" name="bcii_pr_tag" value="<?php echo esc_attr( $tag ); ?>" placeholder="ACCOUNTING · FASB ASU 2023-08" style="width:100%;" />
    </p>
    <?php
}
add_action( 'save_post_bcii_pr', 'bcii_pr_meta_save' );
function bcii_pr_meta_save( $post_id ) {
    if ( ! isset( $_POST['bcii_pr_nonce'] ) || ! wp_verify_nonce( $_POST['bcii_pr_nonce'], 'bcii_pr_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    foreach ( array( 'bcii_pr_external_url', 'bcii_pr_display_date', 'bcii_pr_tag' ) as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            $val = $key === 'bcii_pr_external_url' ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : wp_kses_post( wp_unslash( $_POST[ $key ] ) );
            update_post_meta( $post_id, $key, $val );
        }
    }
}

/* Columnas administrativas: mostrar URL externa, fecha y tag en el listado */
add_filter( 'manage_bcii_pr_posts_columns', function( $cols ) {
    $cols['bcii_pr_date'] = 'Display Date';
    $cols['bcii_pr_tag']  = 'Tag';
    $cols['bcii_pr_url']  = 'External URL';
    return $cols;
} );
add_action( 'manage_bcii_pr_posts_custom_column', function( $col, $post_id ) {
    if ( $col === 'bcii_pr_date' ) echo esc_html( strip_tags( (string) get_post_meta( $post_id, 'bcii_pr_display_date', true ) ) );
    if ( $col === 'bcii_pr_tag' )  echo esc_html( get_post_meta( $post_id, 'bcii_pr_tag', true ) );
    if ( $col === 'bcii_pr_url' )  {
        $u = get_post_meta( $post_id, 'bcii_pr_external_url', true );
        if ( $u ) echo '<a href="' . esc_url( $u ) . '" target="_blank">' . esc_html( wp_parse_url( $u, PHP_URL_HOST ) ) . ' ↗</a>';
        else echo '<em style="color:#c14a2c;">faltante</em>';
    }
}, 10, 2 );

/* -----------------------------------------------
   2e. Shortcode [bcii_press_releases]
   ------------------------------------------------
   Loop de press releases. Cada card abre su propia external_url en _blank.
   Si el post no tiene external_url, se cae al listado de OTC Markets.

   Atributos:
     limit   — cantidad máxima de items (default 20)
     layout  — "list" (default, formato /news/) o "cards" (3-col home)
----------------------------------------------- */
add_shortcode( 'bcii_press_releases', 'bcii_shortcode_press_releases' );
function bcii_shortcode_press_releases( $atts ) {
    $atts = shortcode_atts( array(
        'limit'  => 20,
        'layout' => 'list',
    ), $atts, 'bcii_press_releases' );

    $q = new WP_Query( array(
        'post_type'      => 'bcii_pr',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $atts['limit'],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ) );

    if ( ! $q->have_posts() ) return '';

    $fallback_url = 'https://www.otcmarkets.com/stock/BCII/news';
    $out = '';

    if ( $atts['layout'] === 'cards' ) {
        // Layout para home: 3 news-card en grid
        $out .= '<div class="grid-3">';
        while ( $q->have_posts() ) {
            $q->the_post();
            $url   = get_post_meta( get_the_ID(), 'bcii_pr_external_url', true ) ?: $fallback_url;
            $date  = get_post_meta( get_the_ID(), 'bcii_pr_display_date', true );
            $title = get_the_title();
            $body  = wp_trim_words( get_the_content(), 24, '…' );
            $out  .= '<a href="' . esc_url( $url ) . '" class="news-card" target="_blank" rel="noopener noreferrer">';
            if ( $date )  $out .= '<div class="news-date">' . wp_kses_post( $date ) . '</div>';
            $out  .= '<div class="news-title">' . esc_html( $title ) . '</div>';
            $out  .= '<div class="news-body">' . esc_html( $body ) . '</div>';
            $out  .= '</a>';
        }
        $out .= '</div>';
    } else {
        // Layout list para /news/: filas pr-item, stretched-link
        $out .= '<div class="pr-list" style="display:flex;flex-direction:column;gap:1px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">';
        while ( $q->have_posts() ) {
            $q->the_post();
            $url   = get_post_meta( get_the_ID(), 'bcii_pr_external_url', true ) ?: $fallback_url;
            $date  = get_post_meta( get_the_ID(), 'bcii_pr_display_date', true );
            $tag   = get_post_meta( get_the_ID(), 'bcii_pr_tag', true );
            $title = get_the_title();
            $body  = apply_filters( 'the_content', get_the_content() );
            $body  = wp_strip_all_tags( $body );

            $out  .= '<div class="pr-item">';
            $out  .= '<div class="pr-date-col"><div class="pr-date">' . wp_kses_post( $date ) . '</div></div>';
            $out  .= '<div>';
            $out  .= '<div class="pr-title"><a class="pr-link-stretched" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $title ) . '</a></div>';
            $out  .= '<div class="pr-body">' . esc_html( $body ) . '</div>';
            if ( $tag ) $out .= '<div class="pr-tag">' . esc_html( $tag ) . '</div>';
            $out  .= '</div>';
            $out  .= '</div>';
        }
        $out .= '</div>';
    }

    wp_reset_postdata();
    return $out;
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
