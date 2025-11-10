<?php
/**
 * Template Name: SuperDirectory Listing Page
 *
 * Default template for SuperDirectory-generated listing pages.
 *
 * @package SuperDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main sd-directory-template">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
            </header>

            <div class="entry-content">
                <?php
                /**
                 * Placeholder output. The template will be enhanced in a future iteration
                 * to render directory details for the associated listing.
                 */
                the_content();
                ?>
            </div>
        </article>
    <?php endwhile; ?>
</main>

<?php
get_footer();
