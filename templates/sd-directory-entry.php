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

<style>
/* ===== SuperDirectory Entry - Custom Layout ===== */
article{
    background-image: url(/wp-content/uploads/2025/06/contact-page-logo-mark-bkg.svg?id=3781) !important;
    background-repeat: no-repeat !important;
    background-size: cover !important;
    background-attachment: fixed;
}
.sd-entry-wrap{max-width:1100px;margin:0 auto;padding:65px 16px;}
.sd-entry__path{letter-spacing: 2px;text-transform:uppercase;margin-bottom:10px}
.sd-entry__path_header_holder{text-align:center;}
.sd-entry__path p{font-size:15px;color:#c73e1d; font-weight:600;}
.sd-entry__title{color:#0f172a;margin:0 0 10px}
.sd-entry__intro{color:#334155;margin:0 0 35px;width: 800px;margin-right: auto;margin-left: auto;}

.sd-entry__grid{display:grid;grid-template-columns:300px 1fr;gap:28px;align-items:start}
@media (max-width:980px){.sd-entry__grid{grid-template-columns:1fr;gap:18px}}

.sd-card{
    background: #000F3A !important;
    border-radius: 16px;
    padding: 40px 20px 50px 20px;
}
.sd-card h3{
    margin: 0 0 12px;
    color: #ebeae3;
    font-size: 26px;
    border-bottom: 1px solid #c73e1d; 
}

.sd-meta{margin:0;padding:0}
.sd-meta .sd-meta__row{
    display:flex;
    align-items:flex-start;
    gap:8px;
    padding:4px 0;
}
.sd-meta .sd-meta__row:first-child{border-top:none}
.sd-meta__icon{
    width:15px;
    height:15px;
    margin-top:4px;
    flex-shrink:0;
}
.sd-meta dt{font-weight:bold;color:#6485ff;font-size: 18px;}
.sd-meta dd{
    margin: 0;
    color: #6485ff;
    text-align: left;
    font-size: 14px;
    position: relative;
    top: 0;
}
.sd-meta__text dt{display:block;}
.sd-meta__text dd{display:block;}

.sd-address{
    margin: 0;
    color: #6485ff;
    font-weight: bold;
    font-size: 18px;
}
.sd-address__row{
    display:flex;
    align-items:flex-start;
    gap:8px;
}
.sd-address__icon{
    width:15px;
    height:15px;
    margin-top:4px;
    flex-shrink:0;
}

.sd-connect{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin:12px 0 0;
    padding:0;
    list-style:none;
}
.sd-connect a{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:32px;
    height:32px;
    border-radius:50%;
    background:#c73e1d;
    color:#ebeae3;
    text-decoration:none;
}
.sd-connect a svg{
    width:16px;
    height:16px;
    fill:currentColor;
}

.sd-connect-text{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin:12px 0 0;
    padding:0;
    list-style:none;
}
.sd-connect-text a{
    display:inline-block;
    border:1px solid #e5e7eb;
    border-radius:999px;
    padding:6px 10px;
    text-decoration:none;
    color:#0f172a;
    background:#fff;
}
.sd-connect-text a:hover{text-decoration:underline;border-color:#cbd5e1}

.sd-section{margin:0 0 26px}
.sd-section h2{margin:0 0 10px;color:#ebeae3;}
.sd-section .sd-section__body{color:#ebeae3;}

.sd-section-background{
    background-image: url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png?id=1775) !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    background-size: cover !important;
    border-radius: 30px !important;
    padding:40px 20px 50px 20px;
}

.sd-contact__row span{
    color: #6485ff;
}

/* (Any additional existing styles for this template would still be here) */
</style>


<main id="primary" class="site-main sd-directory-template">

    <?php
    while ( have_posts() ) :
        the_post();

        $entity_id = (int) get_post_meta( get_the_ID(), '_sd_main_entity_id', true );
        $entity    = SD_Main_Entity_Helper::get_entity_for_template( $entity_id );
        //var_dump($entity);
        $address   = array_filter(
            array(
                $entity ? $entity['street_address'] : '',
                $entity ? $entity['city'] : '',
                $entity ? $entity['state'] : '',
                $entity ? $entity['zip_code'] : '',
                $entity ? $entity['country'] : '',
                $entity ? $entity['website_url'] : '',
            )
        );

        $html1 = do_shortcode(<<<'VC'
        [vc_section el_id="title-banner"][vc_row full_width="stretch_row" content_placement="middle" gap="20" css=".vc_custom_1747758575634{padding-top: 170px !important;padding-bottom: 40px !important;background: #000F3A url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png?id=1775) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;border-radius: 30px !important;}" full_width_margin="3vw" row_title="Desktop Banner" visibility="hidden-phone-small"][vc_column][vcex_heading text="This is an individual resource!" text_balance="true" css_animation="fadeIn" tag="span" color="on-accent-alt" font_size="d:50px|tl:50px|tp:50px|pp:38px" font_family="Gantari" font_weight="bold" bottom_margin="15px" line_height="1.1" animation_duration="1.2" text_align="center"][vc_column_text css_animation="fadeIn" css="" color="on-accent-alt" font_size="20px" font_family="Gantari" text_align="center" width="800px"]Superpath Provides You With The Resources & Solutions That Solve <em>ALL</em> Your Business Challenges[/vc_column_text][vcex_button css_animation="fadeInUp" onclick="local_scroll" onclick_url="#contact-us-form" icon_left="id_579" icon_left_size="20px" icon_left_padding="10px" css_wrap=".vc_custom_1761850480949{margin-top: 30px !important;}" animation_delay="0.8" animation_duration="1" align="center"]Visit This Website[/vcex_button][vcex_spacing size="100px"][/vc_column][/vc_row][vc_row full_width="stretch_row" content_placement="middle" gap="20" css=".vc_custom_1747758624185{padding-top: 150px !important;padding-bottom: 80px !important;background: #000F3A url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png?id=1775) !important;background-position: center !important;background-repeat: no-repeat !important;background-size: cover !important;}" visibility="visible-phone-small" wpex_bg_position="bottom left" wpex_bg_overlay="color" wpex_bg_overlay_blend="multiply" wpex_bg_overlay_color="#000f3a" wpex_bg_overlay_opacity="50%" row_title="Mobile Banner"][vc_column offset="vc_hidden-lg vc_hidden-md vc_hidden-sm"][vcex_heading text="This is an individual resource!" css_animation="fadeIn" tag="span" color="on-accent-alt" font_size="d:50px|tl:50px|tp:50px|pp:38px" font_family="Gantari" font_weight="bold" bottom_margin="15px" line_height="1.1" animation_duration="1.2" text_align="center"][vc_column_text css_animation="fadeIn" css="" color="on-accent-alt" font_size="20px" font_family="Gantari" text_align="center" width="800px"]Providing Resources & Solutions that help with <em>ALL</em> your Business Challenges[/vc_column_text][vcex_button css_animation="FadeInUp" onclick="local_scroll" onclick_url="#contact-us-form" icon_left="id_579" icon_left_size="20px" icon_left_padding="10px" css_wrap=".vc_custom_1761850475392{margin-top: 30px !important;}" animation_delay="0.8" animation_duration="1" align="center"]Talk to a Plumbing Lead Gen Expert[/vcex_button][/vc_column][/vc_row][/vc_section]
        VC
        );

        $title = get_the_title();
        $heading = '<h1>' . esc_html($title) . '</h1>';
        $html2 = str_replace("This is an individual resource!", $title , $html1);
        $html3 = str_replace('#contact-us-form', $entity['website_url'] , $html2);
        $html4 = str_replace('Visit This Website', 'Visit ' . $title , $html3);

        echo $html4;

        
        // Creating human-readable variables for category display
        $sd_category_labels = array(
            // Generic options
            'all'                           => 'All Industries',
            'multiple'                      => 'Multiple',

            // Home service business categories (top 30)
            'appliance_repair'              => 'Appliance Repair',
            'carpet_cleaning'               => 'Carpet Cleaning',
            'concrete_masonry'              => 'Concrete & Masonry',
            'deck_patio'                    => 'Deck & Patio',
            'electrical'                    => 'Electrical',
            'fencing'                       => 'Fencing',
            'flooring'                      => 'Flooring',
            'garage_door'                   => 'Garage Door',
            'general_contractor_remodel'    => 'General Contractor & Remodeling',
            'gutter_services'               => 'Gutter Services',
            'handyman'                      => 'Handyman',
            'hardscaping'                   => 'Hardscaping',
            'house_cleaning_maid'           => 'House Cleaning & Maid',
            'hvac'                          => 'HVAC',
            'insulation'                    => 'Insulation',
            'irrigation_sprinklers'         => 'Irrigation & Sprinklers',
            'junk_removal'                  => 'Junk Removal',
            'landscaping'                   => 'Landscaping',
            'moving_storage'                => 'Moving & Storage',
            'painting'                      => 'Painting',
            'pest_control'                  => 'Pest Control',
            'plumbing'                      => 'Plumbing',
            'pool_spa_services'             => 'Pool & Spa Services',
            'pressure_washing'              => 'Pressure Washing',
            'roofing'                       => 'Roofing',
            'security_smart_home'           => 'Security & Smart Home',
            'siding'                        => 'Siding',
            'solar_energy'                  => 'Solar Energy',
            'tree_services'                 => 'Tree Services',
            'water_mold_restoration'        => 'Water & Mold Damage Restoration',

            // Legacy / software-adjacent categories (for backward compatibility)
            'crm'                           => 'CRM',
            'chatbots'                      => 'Chatbots',
            'hiring_platform'               => 'Hiring Platform',
            'lead_generation'               => 'Lead Generation',
            'answering_service'             => 'Answering Service',
            'csr_training'                  => 'CSR Training',
            'business_development'          => 'Business Development',
            'onboarding_companies'          => 'Onboarding Companies',
        );

        // Grab raw DB value
        $category_raw = isset( $entity['category'] ) ? trim( $entity['category'] ) : '';

        // Convert to human-readable label (fallback to raw value)
        $category_label = isset( $sd_category_labels[ $category_raw ] )
            ? $sd_category_labels[ $category_raw ]
            : $category_raw;

        $has_content = ( '' !== trim( get_the_content() ) );
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'sd-directory-entry' ); ?>>
            <div class="sd-entry-wrap">
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
                    <?php
                    $company_name  = get_the_title();
                    $service_model = isset( $entity['service_model'] ) ? (string) $entity['service_model'] : '';
                    $industry_vertical = isset( $entity['industry_vertical'] ) ? (string) $entity['industry_vertical'] : '';
                    ?>
                    <div class="sd-entry__path_header_holder">
                        <div class="sd-entry__path"><p>/ <?php echo esc_html( $category_label ? $category_label : __( 'Category', 'super-directory' ) ); ?> <?php esc_html_e( 'Resources', 'super-directory' ); ?> /</p></div>

                        <header class="entry-header">
                            <h1 class="sd-entry__title"><?php echo esc_html( $company_name ); ?></h1>
                            <?php if ( ! empty( $entity['short_description'] ) ) : ?>
                                <p class="sd-entry__intro"><?php echo wp_kses_post( $entity['short_description'] ); ?></p>
                            <?php endif; ?>
                        </header>
                    </div>

                    <div class="sd-entry__grid">
                        <!-- LEFT: Overview card -->
                        <aside class="sd-card" aria-label="<?php esc_attr_e( 'Overview', 'super-directory' ); ?>">
                            <h3><?php esc_html_e( 'Overview', 'super-directory' ); ?></h3>
                            <dl class="sd-meta">
                                <?php if ( $category_label ) : ?>
                                    <div class="sd-meta__row">
                                        <img src="https://superpath.com/wp-content/uploads/2025/07/phone-call-1.svg" alt="" class="sd-meta__icon">
                                        <div class="sd-meta__text">
                                            <dt><?php esc_html_e( 'Category', 'super-directory' ); ?></dt>
                                            <dd><?php echo esc_html( $category_label ); ?></dd>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ( $service_model ) : ?>
                                    <div class="sd-meta__row">
                                        <img src="https://superpath.com/wp-content/uploads/2025/07/phone-call-1.svg" alt="" class="sd-meta__icon">
                                        <div class="sd-meta__text">
                                            <dt><?php esc_html_e( 'Service Model', 'super-directory' ); ?></dt>
                                            <dd><?php echo esc_html( ucfirst( $service_model ) ); ?></dd>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ( $industry_vertical ) : ?>
                                    <div class="sd-meta__row">
                                        <img src="https://superpath.com/wp-content/uploads/2025/07/phone-call-1.svg" alt="" class="sd-meta__icon">
                                        <div class="sd-meta__text">
                                            <dt><?php esc_html_e( 'Industies Served', 'super-directory' ); ?></dt>
                                            <dd><?php echo esc_html( ucfirst( $industry_vertical ) ); ?></dd>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </dl>

                            <?php if ( ! empty( $entity['street_address'] ) || ! empty( $entity['city'] ) || ! empty( $entity['state'] ) || ! empty( $entity['zip_code'] ) ) : ?>
                                <h3 style="margin-top:16px;"><?php esc_html_e( 'Location', 'super-directory' ); ?></h3>
                                <address class="sd-address">
                                    <div class="sd-address__row">
                                        <img src="https://superpath.com/wp-content/uploads/2025/07/phone-call-1.svg" alt="" class="sd-address__icon">
                                        <div>
                                            <?php if ( ! empty( $entity['street_address'] ) ) : ?>
                                                <div><?php echo esc_html( $entity['street_address'] ); ?></div>
                                            <?php endif; ?>
                                            <div>
                                                <?php
                                                $city_state_zip = array_filter(
                                                    array(
                                                        ! empty( $entity['city'] ) ? $entity['city'] : '',
                                                        ! empty( $entity['state'] ) ? $entity['state'] : '',
                                                        ! empty( $entity['zip_code'] ) ? $entity['zip_code'] : '',
                                                    )
                                                );
                                                echo esc_html( implode( ', ', $city_state_zip ) );
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </address>
                            <?php endif; ?>

                            <?php if ( ! empty( $entity['phone_number'] ) || ! empty( $entity['email_address'] ) ) : ?>
                                <h3 style="margin-top:16px;"><?php esc_html_e( 'Contact', 'super-directory' ); ?></h3>
                                <div class="sd-contact">
                                    <?php if ( ! empty( $entity['phone_number'] ) ) : ?>
                                        <div class="sd-contact__row">
                                            <img src="https://superpath.com/wp-content/uploads/2025/07/phone-call-1.svg" alt="<?php esc_attr_e( 'Phone', 'super-directory' ); ?>" style="width:15px;height:15px;margin-right:8px;vertical-align:middle;">
                                            <span><?php echo esc_html( $entity['phone_number'] ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $entity['email_address'] ) ) : ?>
                                        <div class="sd-contact__row">
                                            <img src="https://superpath.com/wp-content/uploads/2025/07/phone-call-1.svg" alt="<?php esc_attr_e( 'Email', 'super-directory' ); ?>" style="width:15px;height:15px;margin-right:8px;vertical-align:middle;">
                                            <?php
                                            $email = sanitize_email( $entity['email_address'] );
                                            if ( ! empty( $email ) ) :
                                            ?>
                                                <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Connect section
                            ?>
                            <h3 style="margin-top:16px;"><?php esc_html_e( 'Connect', 'super-directory' ); ?></h3>

                            <ul class="sd-connect">
                                <?php if ( ! empty( $entity['facebook_url'] ) ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( $entity['facebook_url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Facebook', 'super-directory' ); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true"><path d="M512 256C512 114.6 397.4 0 256 0S0 114.6 0 256C0 376 82.7 476.8 194.2 504.5V334.2H141.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H287V510.1C413.8 494.8 512 386.9 512 256h0z"/></svg>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! empty( $entity['instagram_url'] ) ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( $entity['instagram_url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Instagram', 'super-directory' ); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8z"/></svg>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! empty( $entity['linkedin_url'] ) ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( $entity['linkedin_url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'LinkedIn', 'super-directory' ); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true"><path d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z"/></svg>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php if ( ! empty( $entity['youtube_url'] ) ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( $entity['youtube_url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'YouTube', 'super-directory' ); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" aria-hidden="true"><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z"/></svg>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <?php if ( ! empty( $entity['google_business_url'] ) || ! empty( $entity['website_url'] ) ) : ?>
                                <ul class="sd-connect-text">
                                    <?php if ( ! empty( $entity['google_business_url'] ) ) : ?>
                                        <li><a href="<?php echo esc_url( $entity['google_business_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Google Business Profile', 'super-directory' ); ?></a></li>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $entity['website_url'] ) ) : ?>
                                        <li><a href="<?php echo esc_url( $entity['website_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Website', 'super-directory' ); ?></a></li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </aside>

                        <!-- RIGHT: Main content -->
                        <div class="sd-section-background">
                            <?php if ( ! empty( $entity['long_description_primary'] ) ) : ?>
                                <section class="sd-section">
                                    <h2><?php printf( esc_html__( 'What %s Does', 'super-directory' ), esc_html( $company_name ) ); ?></h2>
                                    <div class="sd-section__body"><?php echo wp_kses_post( wpautop( $entity['long_description_primary'] ) ); ?></div>
                                </section>
                            <?php endif; ?>

                            <?php if ( ! empty( $entity['long_description_secondary'] ) ) : ?>
                                <section class="sd-section">
                                    <h2><?php printf( esc_html__( 'Why We Recommend %s', 'super-directory' ), esc_html( $company_name ) ); ?></h2>
                                    <div class="sd-section__body"><?php echo wp_kses_post( wpautop( $entity['long_description_secondary'] ) ); ?></div>
                                </section>
                            <?php endif; ?>

                            <?php
                            if ( $has_content ) :
                                ?>
                                <div class="sd-directory-entry__custom-content">
                                    <?php the_content(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </article>


    <?php endwhile; ?>
</main>

<?php

 $html5 = do_shortcode(<<<'VC'
        [vc_row css=".vc_custom_1747761186948{padding-bottom: 60px !important;}" row_title="Blog Section"][vc_column][vcex_heading text="#E-8_JTNDc3BhbiUyMHN0eWxlJTNEJTIyY29sb3IlM0ElMjAlMjM2NDg1RkYlM0IlMjIlM0VUcmFkZSUyMFNlY3JldHMlM0ElM0MlMkZzcGFuJTNFJTIwR2V0JTIwdGhlJTIwTGF0ZXN0JTIwTWFya2V0aW5nJTIwSW5zaWdodHM=" tag="h2" typography_style="wpex-h2" text_align="center"][vc_column_text css="" text_align="center" font_size="20px" width="750px"]Our take on what matters in home services marketing - and how to use it to grow.[/vc_column_text][vcex_spacing size="50px"][wpex_post_cards posts_per_page="3" order="DESC" link_type="post" card_style="template_126" grid_spacing="30"][/vc_column][/vc_row]
        VC
        );

echo $html5;

?>

<?php
get_footer();
