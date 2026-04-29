<div class="post-row">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="post-media">
			<div class='media-inner'>
				<a href="<?php the_permalink() ?>" title="<?php the_title_attribute()?>">
					<?php the_post_thumbnail( 'full' ) ?>
				</a>
				<a href="<?php the_permalink() ?>" title="<?php the_title_attribute() ?>" class="read-more"><?php esc_html_e( 'Read More', 'execor' ) ?></a>
			</div>
		</div>
	<?php endif; ?>
	<div class="post-content-outer">
		<div class="vamtam-categories">
			<?php the_category( ', ' ); ?>
		</div>

		<?php
			include locate_template( 'templates/post/header-large.php' );
		?>

		<!-- <div class="vamtam-excerpt"> -->
			<?php //the_excerpt() ?>
		<!-- </div> -->

		<?php if ( ! has_post_thumbnail() ) : ?>
			<a href="<?php the_permalink() ?>" title="<?php the_title_attribute() ?>" class="read-more"><?php esc_html_e( 'Read More', 'execor' ) ?></a>
		<?php endif; ?>

		<?php get_template_part( 'templates/post/meta/date' ) ?>
	</div>
</div>
