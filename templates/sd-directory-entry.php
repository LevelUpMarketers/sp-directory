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

        $entity_id = (int) get_post_meta( get_the_ID(), '_sd_main_entity_id', true );
        $entity    = SD_Main_Entity_Helper::get_entity_for_template( $entity_id );
        $address   = array_filter(
            array(
                $entity ? $entity['street_address'] : '',
                $entity ? $entity['city'] : '',
                $entity ? $entity['state'] : '',
                $entity ? $entity['zip_code'] : '',
                $entity ? $entity['country'] : '',
            )
        );

        $has_content = ( '' !== trim( get_the_content() ) );
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'sd-directory-entry' ); ?>>
            <header class="entry-header sd-directory-entry__header">
                <?php the_title( '<h1 class="entry-title sd-directory-entry__title">', '</h1>' ); ?>

                <?php if ( $entity && $entity['short_description'] ) : ?>
                    <div class="sd-directory-entry__summary"><?php echo wp_kses_post( wpautop( $entity['short_description'] ) ); ?></div>
                <?php endif; ?>
            </header>

            <div class="entry-content sd-directory-entry__content">
                <?php if ( ! $entity ) : ?>
                    <p class="sd-directory-entry__notice">
                        <?php
                        printf(
                            esc_html__( 'The directory details for %s are currently unavailable.', 'super-directory' ),
                            esc_html( get_the_title() )
                        );
                        ?>
                    </p>
                <?php else : ?>
                    <div class="sd-directory-entry__layout">
                        <div class="sd-directory-entry__main">
                            <?php if ( $entity['long_description_primary'] ) : ?>
                                <section class="sd-directory-entry__section sd-directory-entry__section--primary">
                                    <h2 class="sd-directory-entry__section-title"><?php esc_html_e( 'About', 'super-directory' ); ?></h2>
                                    <div class="sd-directory-entry__section-content"><?php echo wp_kses_post( $entity['long_description_primary'] ); ?></div>
                                </section>
                            <?php endif; ?>

                            <?php if ( $entity['long_description_secondary'] ) : ?>
                                <section class="sd-directory-entry__section sd-directory-entry__section--secondary">
                                    <h2 class="sd-directory-entry__section-title"><?php esc_html_e( 'Additional Details', 'super-directory' ); ?></h2>
                                    <div class="sd-directory-entry__section-content"><?php echo wp_kses_post( $entity['long_description_secondary'] ); ?></div>
                                </section>
                            <?php endif; ?>
                        </div>

                        <aside class="sd-directory-entry__sidebar" aria-label="<?php esc_attr_e( 'Company details', 'super-directory' ); ?>">
                            <?php if ( $entity['category'] || $entity['industry_vertical'] || $entity['service_model'] ) : ?>
                                <section class="sd-directory-entry__card">
                                    <h2 class="sd-directory-entry__card-title"><?php esc_html_e( 'Overview', 'super-directory' ); ?></h2>
                                    <dl class="sd-directory-entry__meta-list">
                                        <?php if ( $entity['category'] ) : ?>
                                            <div class="sd-directory-entry__meta-item">
                                                <dt><?php esc_html_e( 'Category', 'super-directory' ); ?></dt>
                                                <dd><?php echo esc_html( $entity['category'] ); ?></dd>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( $entity['industry_vertical'] ) : ?>
                                            <div class="sd-directory-entry__meta-item">
                                                <dt><?php esc_html_e( 'Industry Focus', 'super-directory' ); ?></dt>
                                                <dd><?php echo esc_html( $entity['industry_vertical'] ); ?></dd>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( $entity['service_model'] ) : ?>
                                            <div class="sd-directory-entry__meta-item">
                                                <dt><?php esc_html_e( 'Service Model', 'super-directory' ); ?></dt>
                                                <dd><?php echo esc_html( $entity['service_model'] ); ?></dd>
                                            </div>
                                        <?php endif; ?>
                                    </dl>
                                </section>
                            <?php endif; ?>

                            <?php if ( $entity['website_url'] || $entity['phone_number'] || $entity['email_address'] ) : ?>
                                <section class="sd-directory-entry__card">
                                    <h2 class="sd-directory-entry__card-title"><?php esc_html_e( 'Get in Touch', 'super-directory' ); ?></h2>
                                    <ul class="sd-directory-entry__link-list">
                                        <?php if ( $entity['website_url'] ) : ?>
                                            <li><a href="<?php echo esc_url( $entity['website_url'] ); ?>" class="sd-directory-entry__link" rel="nofollow noopener" target="_blank"><?php esc_html_e( 'Visit Website', 'super-directory' ); ?></a></li>
                                        <?php endif; ?>
                                        <?php if ( $entity['phone_number'] ) : ?>
                                            <li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9\+]/', '', $entity['phone_number'] ) ); ?>" class="sd-directory-entry__link"><?php echo esc_html( $entity['phone_number'] ); ?></a></li>
                                        <?php endif; ?>
                                        <?php if ( $entity['email_address'] ) : ?>
                                            <li><a href="mailto:<?php echo esc_attr( $entity['email_address'] ); ?>" class="sd-directory-entry__link"><?php echo esc_html( $entity['email_address'] ); ?></a></li>
                                        <?php endif; ?>
                                    </ul>
                                </section>
                            <?php endif; ?>

                            <?php if ( ! empty( $address ) ) : ?>
                                <section class="sd-directory-entry__card">
                                    <h2 class="sd-directory-entry__card-title"><?php esc_html_e( 'Location', 'super-directory' ); ?></h2>
                                    <address class="sd-directory-entry__address">
                                        <?php echo esc_html( implode( ', ', $address ) ); ?>
                                    </address>
                                </section>
                            <?php endif; ?>

                            <?php
                            $social_links = array(
                                'facebook_url'        => __( 'Facebook', 'super-directory' ),
                                'instagram_url'       => __( 'Instagram', 'super-directory' ),
                                'youtube_url'         => __( 'YouTube', 'super-directory' ),
                                'linkedin_url'        => __( 'LinkedIn', 'super-directory' ),
                                'google_business_url' => __( 'Google Business Profile', 'super-directory' ),
                            );

                            $visible_social = array();

                            foreach ( $social_links as $key => $label ) {
                                if ( ! empty( $entity[ $key ] ) ) {
                                    $visible_social[] = array(
                                        'url'   => $entity[ $key ],
                                        'label' => $label,
                                    );
                                }
                            }
                            ?>

                            <?php if ( ! empty( $visible_social ) ) : ?>
                                <section class="sd-directory-entry__card">
                                    <h2 class="sd-directory-entry__card-title"><?php esc_html_e( 'Connect', 'super-directory' ); ?></h2>
                                    <ul class="sd-directory-entry__link-list">
                                        <?php foreach ( $visible_social as $item ) : ?>
                                            <li><a href="<?php echo esc_url( $item['url'] ); ?>" class="sd-directory-entry__link" rel="nofollow noopener" target="_blank"><?php echo esc_html( $item['label'] ); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </section>
                            <?php endif; ?>
                        </aside>
                    </div>
                <?php endif; ?>

                <?php
                if ( $has_content ) {
                    echo '<div class="sd-directory-entry__custom-content">';
                    the_content();
                    echo '</div>';
                }
                ?>
            </div>
        </article>
    <?php endwhile; ?>
</main>

<?php
get_footer();
