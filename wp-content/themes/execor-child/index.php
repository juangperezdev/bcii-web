<?php get_header(); ?>

<main id="main-content" class="site-main">

    <?php if ( have_posts() ) : ?>

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>

            </article>

        <?php endwhile; ?>

    <?php else : ?>

        <p><?php _e( 'No se encontró contenido.', 'execor-child' ); ?></p>

    <?php endif; ?>

</main>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
