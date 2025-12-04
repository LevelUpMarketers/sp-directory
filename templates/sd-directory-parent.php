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

        $page_title   = 'Home Services Resources';
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
        [vc_section el_id="title-banner"][vc_row full_width="stretch_row" content_placement="middle" gap="20" css=".vc_custom_1747758575634{padding-top: 170px !important;padding-bottom: 40px !important;background: #000F3A url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;border-radius: 30px !important;}" full_width_margin="3vw" row_title="Desktop Banner" visibility="hidden-phone-small"][vc_column][vcex_heading text="This is an individual resource!" text_balance="true" css_animation="fadeIn" tag="span" color="on-accent-alt" font_size="d:50px|tl:50px|tp:50px|pp:38px" font_family="Gantari" font_weight="bold" bottom_margin="15px" line_height="1.1" animation_duration="1.2" text_align="center"][vc_column_text css_animation="fadeIn" css="" color="on-accent-alt" font_size="20px" font_family="Gantari" text_align="center" width="800px"]We go beyond just what services we offer to help you grow - we partner with other resources & solutions that solve <em>ALL</em> your Home Services business challenges[/vc_column_text][vcex_button css_animation="fadeInUp" onclick="local_scroll" onclick_url="#directory-search" icon_left="id_579" icon_left_size="20px" icon_left_padding="10px" css_wrap=".vc_custom_1761850480949{margin-top: 30px !important;}" animation_delay="0.8" animation_duration="1" align="center"]Explore Resources[/vcex_button][vcex_spacing size="100px"][/vc_column][/vc_row][vc_row full_width="stretch_row" content_placement="middle" gap="20" css=".vc_custom_1747758624185{padding-top: 150px !important;padding-bottom: 80px !important;background: #000F3A url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;}" visibility="visible-phone-small" wpex_bg_position="bottom left" wpex_bg_overlay="color" wpex_bg_overlay_blend="multiply" wpex_bg_overlay_color="#000f3a" wpex_bg_overlay_opacity="50%" row_title="Mobile Banner"][vc_column offset="vc_hidden-lg vc_hidden-md vc_hidden-sm"][vcex_heading text="This is an individual resource!" css_animation="fadeIn" tag="span" color="on-accent-alt" font_size="d:50px|tl:50px|tp:50px|pp:38px" font_family="Gantari" font_weight="bold" bottom_margin="15px" line_height="1.1" animation_duration="1.2" text_align="center"][vc_column_text css_animation="fadeIn" css="" color="on-accent-alt" font_size="20px" font_family="Gantari" text_align="center" width="800px"]We go beyond just what services we offer to help you grow - we partner with other resources & solutions that solve <em>ALL</em> your Home Services business challenges[/vc_column_text][vcex_button css_animation="FadeInUp" onclick="local_scroll" onclick_url="#directory-search" icon_left="id_579" icon_left_size="20px" icon_left_padding="10px" css_wrap=".vc_custom_1761850475392{margin-top: 30px !important;}" animation_delay="0.8" animation_duration="1" align="center"]Browse Resources[/vcex_button][/vc_column][/vc_row][/vc_section]
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
            <style>
    

    .jre-hacked-context-row{
        max-width: var(--wpex-container-max-width);
        width: var(--wpex-container-width);
        padding-top: 60px !important;
        padding-bottom: 60px !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }
</style>

<div class="jre-hacked-context-row vc_row wpb_row vc_row-fluid vc_custom_1747761079977 vc_column-gap-15 vc_row-o-content-middle vc_row-flex wpex-relative wpex-cols-right"><div class="wpb_column vc_column_container vc_col-sm-6"><div class="vc_column-inner"><div class="wpb_wrapper">
    <div style="color:#c73e1d;font-size:15px;letter-spacing:2px;font-weight:600;text-transform:uppercase;" class="wpb_text_column has-custom-color wpex-child-inherit-color wpb_content_element vc_custom_1748960952483">
        <div class="wpb_wrapper">
            <p>/&nbsp; RESOURCES TO GROW YOUR BUSINESS&nbsp; /</p>

        </div>
    </div>
<style>.vcex-heading.vcex_692de10a2ca5e{margin-block-end:20px;}</style><h1 class="vcex-heading vcex-heading-plain vcex-module wpex-h1 vcex_692de10a2ca5e"><span class="vcex-heading-inner wpex-inline-block">Don't Spend Hours Researching Solutions -  <span style="color: #6485FF">Browse Superpath's Vetted Resources for the Trades</span></span></h1>
    <div class="wpb_text_column wpb_content_element">
        <div class="wpb_wrapper">
            <p>If you run a home services business, you already know how overwhelming it can be to sort through dozens of tools, vendors, and platforms promising to make your life easier. From CRMs and phone systems to scheduling tools, review platforms, and marketing solutions, it’s hard to know what actually works for the trades - and what’s just noise.</p>
<p>Superpath takes the guesswork out by curating and vetting the most trusted providers in the industry, who also just happen to be some of our closest partners. Our resources directory below lets you quickly search for trusted tools built to help trades businesses run smoother, grow faster, and serve customers better. Every listing is carefully reviewed so you can find what you need - and make confident decisions.</p>
<p>Questions about one of the resources below? Not sure if they're an exact fit for your needs? Then be sure to ask your Customer Service Manager! 

        </div>
    </div>
</div></div></div><div class="wpb_column vc_column_container vc_col-sm-6"><div class="vc_column-inner"><div class="wpb_wrapper"><style>.vcex-image.vcex_692de10a2ce7f .vcex-image-img{border-radius:20px;height:450px;object-position:left;}</style><figure class="vcex-image vcex-module wpex-text-center vcex_692de10a2ce7f"><div class="vcex-image-inner wpex-relative wpex-w-100"><img loading="lazy" class="vcex-image-img wpex-align-middle wpex-w-100 wpex-object-cover" alt="SEO Marketing for Home Service Companies" decoding="async" src="/wp-content/uploads/2025/06/seo-h1-section-2-600x400.jpg" srcset="/wp-content/uploads/2025/06/seo-h1-section-2-600x400.jpg 600w, /wp-content/uploads/2025/06/seo-h1-section-2-300x200.jpg 300w, /wp-content/uploads/2025/06/seo-h1-section-2-1024x682.jpg 1024w, /wp-content/uploads/2025/06/seo-h1-section-2-768x512.jpg 768w, /wp-content/uploads/2025/06/seo-h1-section-2-1536x1024.jpg 1536w, /wp-content/uploads/2025/06/seo-h1-section-2-200x133.jpg 200w, /wp-content/uploads/2025/06/seo-h1-section-2.jpg 2000w" width="600" height="400"></div></figure></div></div></div></div>



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
                                <option value="" disabled selected><?php esc_html_e( 'Select a Category...', 'super-directory' ); ?></option>
                                <?php foreach ( $categories as $category_value ) : ?>
                                    <option value="<?php echo esc_attr( $category_value ); ?>"><?php echo esc_html( SD_Main_Entity_Helper::get_category_label( $category_value ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="sd-directory-search-industry"><?php esc_html_e( 'Industry', 'super-directory' ); ?></label>
                            <select id="sd-directory-search-industry" name="industry">
                                <option value="" disabled selected><?php esc_html_e( 'Select an Industry...', 'super-directory' ); ?></option>
                                <?php foreach ( $industries as $industry_value ) : ?>
                                    <option value="<?php echo esc_attr( $industry_value ); ?>"><?php echo esc_html( SD_Main_Entity_Helper::get_industry_label( $industry_value ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="sd-directory-search-state"><?php esc_html_e( 'State', 'super-directory' ); ?></label>
                            <select id="sd-directory-search-state" name="state">
                                <option value="" disabled selected><?php esc_html_e( 'Select a State...', 'super-directory' ); ?></option>
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
[vc_row css=".vc_custom_1747761186948{padding-bottom: 60px !important;}" row_title="Blog Section"][vc_column]

[vcex_heading text="#E-8_JTNDc3BhbiUyMHN0eWxlJTNEJTIyY29sb3IlM0ElMjAlMjM2NDg1RkYlM0IlMjIlM0VUcmFkZSUyMFNlY3JldHMlM0ElM0MlMkZzcGFuJTNFJTIwR2V0JTIwdGhlJTIwTGF0ZXN0JTIwTWFya2V0aW5nJTIwSW5zaWdodHM=" tag="h2" typography_style="wpex-h2" text_align="center"]

[vc_column_text css="" text_align="center" font_size="20px" width="750px"]
Our take on what matters in home services marketing - and how to use it to grow.
[/vc_column_text]

[vcex_spacing size="50px"]

[wpex_post_cards 
    posts_per_page="6" 
    order="DESC" 
    orderby="date" 
    link_type="post" 
    card_style="template_126" 
    grid_spacing="30"
][/wpex_post_cards]

[/vc_column][/vc_row]
VC
);

$contactform = do_shortcode(<<<'VC'
[vc_section full_width="stretch_row" css=".vc_custom_1764852117216{padding-top: 80px !important;padding-bottom: 20px !important;background-color: #000F3A !important;}" local_scroll_id="contact-us-form" el_id="contact-us-form"][vc_row css_animation="appear" content_placement="middle" column_spacing="30" equal_height="yes" remove_bottom_col_margin="true" css=".vc_custom_1753276349623{margin-top: 10px !important;padding-top: 3vw !important;padding-right: 3vw !important;padding-bottom: 2.5vw !important;padding-left: 3vw !important;background-image: url(/wp-content/uploads/2025/05/footer-cta-background-gradient.svg?id=2323) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;border-radius: 30px !important;}" row_title="Desktop Footer CTA" wpex_bg_position="bottom"][vc_column width="5/12" css=".vc_custom_1753216454068{margin-top: -40px !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;}"][vcex_image image_id="1049" object_fit="contain" css=".vc_custom_1756130212495{margin-bottom: -10px !important;padding-top: 0px !important;padding-bottom: 0px !important;}" border_radius="0px 0px 10px 15px" height="250px"][vc_row_inner equal_height="yes" gap="30" css=".vc_custom_1753215450228{margin-top: 0px !important;padding-top: 20px !important;padding-right: 15px !important;padding-bottom: 20px !important;padding-left: 15px !important;background-color: #000F3A !important;border-radius: 25px !important;}" el_class="glass-effect"][vc_column_inner css=".vc_custom_1753202828060{margin-bottom: 0px !important;padding-bottom: 0px !important;background-position: center !important;background-repeat: no-repeat !important;background-size: contain !important;border-radius: 10px !important;}"][vcex_heading text="Let’s get to work." tag="span" color="accent" font_size="24px" font_family="Gantari" font_weight="semibold" css=".vc_custom_1753213716839{padding-bottom: 8px !important;padding-left: 2px !important;}" text_align="center"][vcex_heading text="The trades are built on skill. Growth is built on strategy." tag="span" color="on-accent-alt" font_size="30px" font_family="Gantari" font_weight="bold" line_height="1.2" bottom_margin="10px" css=".vc_custom_1754561606772{padding-left: 3px !important;}" text_align="center"][vc_column_text css=".vc_custom_1754561620832{padding-left: 5px !important;}" color="on-accent-alt" text_align="center"]Book a consultation with an industry expert and put your business on a Superpath today.[/vc_column_text][/vc_column_inner][/vc_row_inner][vcex_spacing visibility="visible-phone"][/vc_column][vc_column width="7/12" css=".vc_custom_1753218293396{margin-right: 15px !important;margin-left: 20px !important;}" el_class="contact-form-links"][gravityform id="1" title="false" description="false" ajax="true"][/vc_column][/vc_row][/vc_section]
VC
);


echo $blog_section;
echo $contactform;

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
