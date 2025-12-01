<?php
/**
 * Template Name: SuperDirectory Parent Page
 *
 * Directory landing page with search, filters, and paginated resource cards.
 *
 * @package SuperDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

?>

<main id="primary" class="site-main sd-directory-parent">
    <?php
    while ( have_posts() ) :
        the_post();

        $page_title   = get_the_title();
        $path_label   = __( 'Resources', 'super-directory' );
        $intro        = get_the_excerpt();
        $logo_markup  = '';
        $content_copy = trim( get_the_content() );

        if ( '' === $intro && '' !== $content_copy ) {
            $intro = wp_trim_words( wp_strip_all_tags( $content_copy ), 35, '…' );
        }

        if ( has_post_thumbnail() ) {
            $logo_markup = get_the_post_thumbnail( null, 'medium', array( 'class' => 'sd-entry__logo-image' ) );
        }

        $hero_html = do_shortcode(<<<'VC'
        [vc_section el_id="title-banner"][vc_row full_width="stretch_row" content_placement="middle" gap="20" css=".vc_custom_1747758575634{padding-top: 170px !important;padding-bottom: 40px !important;background: #000F3A url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png?id=1775) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;border-radius: 30px !important;}" full_width_margin="3vw" row_title="Desktop Banner" visibility="hidden-phone-small"][vc_column][vcex_heading text="This is an individual resource!" text_balance="true" css_animation="fadeIn" tag="span" color="on-accent-alt" font_size="d:50px|tl:50px|tp:50px|pp:38px" font_family="Gantari" font_weight="bold" bottom_margin="15px" line_height="1.1" animation_duration="1.2" text_align="center"][vc_column_text css_animation="fadeIn" css="" color="on-accent-alt" font_size="20px" font_family="Gantari" text_align="center" width="800px"]Superpath Provides You With The Resources & Solutions That Solve <em>ALL</em> Your Business Challenges[/vc_column_text][vcex_button css_animation="fadeInUp" onclick="local_scroll" onclick_url="#directory-search" icon_left="id_579" icon_left_size="20px" icon_left_padding="10px" css_wrap=".vc_custom_1761850480949{margin-top: 30px !important;}" animation_delay="0.8" animation_duration="1" align="center"]Explore Resources[/vcex_button][vcex_spacing size="100px"][/vc_column][/vc_row][vc_row full_width="stretch_row" content_placement="middle" gap="20" css=".vc_custom_1747758624185{padding-top: 150px !important;padding-bottom: 80px !important;background: #000F3A url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png?id=1775) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;}" visibility="visible-phone-small" wpex_bg_position="bottom left" wpex_bg_overlay="color" wpex_bg_overlay_blend="multiply" wpex_bg_overlay_color="#000f3a" wpex_bg_overlay_opacity="50%" row_title="Mobile Banner"][vc_column offset="vc_hidden-lg vc_hidden-md vc_hidden-sm"][vcex_heading text="This is an individual resource!" css_animation="fadeIn" tag="span" color="on-accent-alt" font_size="d:50px|tl:50px|tp:50px|pp:38px" font_family="Gantari" font_weight="bold" bottom_margin="15px" line_height="1.1" animation_duration="1.2" text_align="center"][vc_column_text css_animation="fadeIn" css="" color="on-accent-alt" font_size="20px" font_family="Gantari" text_align="center" width="800px"]Providing Resources & Solutions that help with <em>ALL</em> your Business Challenges[/vc_column_text][vcex_button css_animation="FadeInUp" onclick="local_scroll" onclick_url="#directory-search" icon_left="id_579" icon_left_size="20px" icon_left_padding="10px" css_wrap=".vc_custom_1761850475392{margin-top: 30px !important;}" animation_delay="0.8" animation_duration="1" align="center"]Browse Resources[/vcex_button][/vc_column][/vc_row][/vc_section]
        VC
        );

        $hero_html = str_replace( 'This is an individual resource!', $page_title, $hero_html );

        echo $hero_html;

        $categories       = SD_Main_Entity_Helper::get_distinct_values( 'category' );
        $industries       = SD_Main_Entity_Helper::get_distinct_values( 'industry_vertical' );
        $states           = SD_Main_Entity_Helper::get_distinct_values( 'state' );
        $initial_results  = SD_Main_Entity_Helper::search_directory_entries( array( 'page' => 1, 'per_page' => 9 ) );
        $initial_items    = isset( $initial_results['items'] ) ? $initial_results['items'] : array();
        $card_button_text = __( 'Learn More', 'super-directory' );
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class( 'sd-directory-parent__article' ); ?>>
            <div class="sd-parent-wrap">
                <div class="sd-entry__path_header_holder">
                    <div class="sd-entry__path"><p>/ <?php echo esc_html( $path_label ); ?> /</p></div>

                    <header class="entry-header">
                        <?php if ( '' !== $intro ) : ?>
                            <p class="sd-entry__intro"><?php echo esc_html( $intro ); ?></p>
                        <?php endif; ?>
                        <?php if ( $logo_markup ) : ?>
                            <div class="sd-entry__logo">
                                <?php echo wp_kses_post( $logo_markup ); ?>
                            </div>
                        <?php endif; ?>
                    </header>
                </div>

                <div class="sd-directory-search" id="directory-search">
                    <h2><?php esc_html_e( 'Search & Filter Resources', 'super-directory' ); ?></h2>
                    <p><?php esc_html_e( 'Choose any combination of filters to find the right resource, then click Search to apply them.', 'super-directory' ); ?></p>
                    <form class="sd-directory-search__form" method="post" action="#">
                        <div>
                            <label for="sd-directory-search-name"><?php esc_html_e( 'Search by name', 'super-directory' ); ?></label>
                            <input type="text" id="sd-directory-search-name" name="search" placeholder="<?php esc_attr_e( 'Enter a resource name', 'super-directory' ); ?>" />
                        </div>
                        <div>
                            <label for="sd-directory-search-category"><?php esc_html_e( 'Category', 'super-directory' ); ?></label>
                            <select id="sd-directory-search-category" name="category">
                                <option value="" disabled selected><?php esc_html_e( 'Choose a Category...', 'super-directory' ); ?></option>
                                <option value=""><?php esc_html_e( 'Any category', 'super-directory' ); ?></option>
                                <?php foreach ( $categories as $category_value ) : ?>
                                    <option value="<?php echo esc_attr( $category_value ); ?>"><?php echo esc_html( SD_Main_Entity_Helper::get_category_label( $category_value ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="sd-directory-search-industry"><?php esc_html_e( 'Industry', 'super-directory' ); ?></label>
                            <select id="sd-directory-search-industry" name="industry">
                                <option value="" disabled selected><?php esc_html_e( 'Choose an Industry...', 'super-directory' ); ?></option>
                                <option value=""><?php esc_html_e( 'Any industry', 'super-directory' ); ?></option>
                                <?php foreach ( $industries as $industry_value ) : ?>
                                    <option value="<?php echo esc_attr( $industry_value ); ?>"><?php echo esc_html( SD_Main_Entity_Helper::get_industry_label( $industry_value ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="sd-directory-search-state"><?php esc_html_e( 'State', 'super-directory' ); ?></label>
                            <select id="sd-directory-search-state" name="state">
                                <option value="" disabled selected><?php esc_html_e( 'Choose a State...', 'super-directory' ); ?></option>
                                <option value=""><?php esc_html_e( 'Any state', 'super-directory' ); ?></option>
                                <?php foreach ( $states as $state_value ) : ?>
                                    <option value="<?php echo esc_attr( $state_value ); ?>"><?php echo esc_html( $state_value ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sd-directory-search__actions">
                            <button type="submit" class="sd-directory-search__submit"><?php esc_html_e( 'Search', 'super-directory' ); ?></button>
                            <button type="button" class="sd-directory-search__reset"><?php esc_html_e( 'Reset search', 'super-directory' ); ?></button>
                        </div>
                    </form>
                </div>

                <div class="sd-directory-status" role="status" aria-live="polite">
                    <?php
                    if ( empty( $initial_items ) ) {
                        esc_html_e( 'No resources match your filters yet.', 'super-directory' );
                    }
                    ?>
                </div>
                <div class="sd-directory-results">
                    <?php foreach ( $initial_items as $item ) : ?>
                        <?php
                        $card_classes = array( 'sd-directory-card' );
                        $card_style   = '';

                        if ( ! empty( $item['homepage_screenshot'] ) ) {
                            $card_classes[] = 'has-screenshot';
                            $card_style     = sprintf( '--sd-card-screenshot: url("%s");', esc_url( $item['homepage_screenshot'] ) );
                        }
                        ?>
                        <?php if ( ! empty( $item['permalink'] ) ) : ?>
                            <a class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" href="<?php echo esc_url( $item['permalink'] ); ?>" style="<?php echo esc_attr( $card_style ); ?>">
                        <?php else : ?>
                            <article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>" style="<?php echo esc_attr( $card_style ); ?>">
                        <?php endif; ?>
                                <div class="sd-directory-card__logo">
                                    <?php if ( ! empty( $item['logo'] ) ) : ?>
                                        <img src="<?php echo esc_url( $item['logo'] ); ?>" alt="<?php echo esc_attr( $item['name'] ? sprintf( __( '%s logo', 'super-directory' ), $item['name'] ) : '' ); ?>" />
                                    <?php endif; ?>
                                </div>
                                <h3 class="sd-directory-card__title"><?php echo esc_html( $item['name'] ); ?></h3>
                                <p class="sd-directory-card__meta"><?php echo esc_html( implode( ' • ', array_filter( array( isset( $item['category_label'] ) ? $item['category_label'] : '', isset( $item['industry_label'] ) ? $item['industry_label'] : '' ) ) ) ); ?></p>
                                <span class="sd-directory-card__cta"><?php echo esc_html( $card_button_text ); ?></span>
                        <?php if ( ! empty( $item['permalink'] ) ) : ?>
                            </a>
                        <?php else : ?>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="sd-directory-load-sentinel" aria-hidden="true">
                        <div class="sd-directory-load-indicator"></div>
                    </div>
                </div>
                <div class="sd-directory-pagination"></div>
            </div>
        </article>
    <?php endwhile; ?>
</main>

<?php
$blog_section = do_shortcode(<<<'VC'
        [vc_row css=".vc_custom_1747761186948{padding-bottom: 60px !important;}" row_title="Blog Section"][vc_column][vcex_heading text="#E-8_JTNDc3BhbiUyMHN0eWxlJTNEJTIyY29sb3IlM0ElMjAlMjM2NDg1RkYlM0IlMjIlM0VUcmFkZSUyMFNlY3JldHMlM0ElM0MlMkZzcGFuJTNFJTIwR2V0JTIwdGhlJTIwTGF0ZXN0JTIwTWFya2V0aW5nJTIwSW5zaWdodHM=" tag="h2" typography_style="wpex-h2" text_align="center"][vc_column_text css="" text_align="center" font_size="20px" width="750px"]Our take on what matters in home services marketing - and how to use it to grow.[/vc_column_text][vcex_spacing size="50px"][wpex_post_cards posts_per_page="3" order="DESC" link_type="post" card_style="template_126" grid_spacing="30"][/vc_column][/vc_row]
        VC
);

echo $blog_section;

wp_localize_script(
    'sd-directory-parent',
    'sdDirectoryParent',
    array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'sd_directory_search' ),
        'perPage'  => isset( $initial_results['per_page'] ) ? (int) $initial_results['per_page'] : 9,
        'initial'  => $initial_results,
        'strings'  => array(
            'noResults'    => __( 'No resources match your filters yet.', 'super-directory' ),
            'error'        => __( 'Unable to load resources right now. Please try again.', 'super-directory' ),
            'viewResource' => $card_button_text,
            'prev'         => __( 'Previous', 'super-directory' ),
            'next'         => __( 'Next', 'super-directory' ),
            'pageOf'       => __( 'Page %1$s of %2$s', 'super-directory' ),
        ),
    )
);

get_footer();
