<?php
/**
 * BCII — Single page template (override del page.php del parent Execor)
 *
 * El parent Execor envuelve todo en .page-wrapper / .vamtam-main / sidebar,
 * con CSS pesado de Vamtam. Para nuestro diseño BCII queremos that the_content
 * renderice directamente sin wrappers extra — el header.php abre <main class="bcii-main">
 * y footer.php lo cierra.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        the_content();
    endwhile;
endif;

get_footer();
