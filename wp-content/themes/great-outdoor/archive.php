<?php get_header(); ?>

<section class="main-content">

    <?php
        $adventurePage = new WP_Query(array('post_type' => 'page', 'name' => 'adventure'));

        if ( $adventurePage -> have_posts() ) :

        while ( $adventurePage -> have_posts() ) : $adventurePage -> the_post(); ?>


                <?php include( get_template_directory() . '/includes/content-parser.php'); ?>

        <?php endwhile; ?>


                <?php wp_reset_postdata(); ?>

        <?php else : ?>
        <?php endif; ?>


    <?php //include( get_template_directory() . '/includes/content-parser.php'); ?>

    <h2 class="section-header is-page-title">
        <?php the_archive_title(); ?>
    </h2>



<div class="archive-view">

<?php

    echo '<div class="post-filter">';

        /*wp_list_categories(
            array(
                'depth' => 1,
                'taxonomy' => 'category',
            ));

        wp_list_categories(
            array(
                'title_li' => 'Regions',
                'taxonomy' => 'regions',
            )
        );

        wp_list_categories(
            array(
                'title_li' => 'Activities',
                'taxonomy' => 'activities',
            )
        );*/

echo do_shortcode( '[searchandfilter id="34148"]' );
    echo '</div>';


?>


    <ul class="tiled-list">


<?php if ( have_posts() ) :

        include('includes/random-image-counter.php');

        while ( have_posts() ) : the_post(); ?>

        <?php
        $postImage = get_the_post_thumbnail();

        include('includes/random-image-generator.php');
        ?>

        <li class="tile">
            <a href="<?php esc_url( the_permalink() ); ?>"><?php echo $postImage; ?></a>
            <a href="<?php esc_url( the_permalink() ); ?>"><h3><?php esc_html( the_title() ); ?></h3></a>
            <?php
                $body = get_the_excerpt();
                echo esc_html(substr( $body, 0, strpos( $body, ' ', 85 ))) . '...';
            ?>
        </li>
<?php endwhile; ?>

        <div class="pagination-link"><?php posts_nav_link(); ?></div>

        <?php wp_reset_postdata(); ?>

<?php else : ?>
    <p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
    </ul>
<?php endif; ?>

</div><!--.archive-view-->


</section><!--.main-content-->
<?php get_footer(); ?>
