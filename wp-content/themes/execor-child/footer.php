<?php
/**
 * BCII — Child theme footer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$logo_white_id  = 10002; // bcii-logo-white
$logo_white_url = wp_get_attachment_image_url( $logo_white_id, 'full' );
$year           = date( 'Y' );
?>
</main><!-- /.bcii-main -->

<footer class="bcii-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo">
                    <?php if ( $logo_white_url ) : ?>
                        <img src="<?php echo esc_url( $logo_white_url ); ?>" alt="BCII Enterprises">
                    <?php endif; ?>
                    <span class="footer-brand-text">
                        <span class="name">ENTERPRISES</span>
                        <span class="sub">INC.</span>
                    </span>
                </a>
                <p class="footer-desc">A blockchain-focused financial technology company headquartered in Vero Beach, Florida. Developing the patent-pending Super Coupon Token platform to transform distribution-list assets into tradeable digital instruments.</p>
                <span class="footer-ticker-badge">OTC PINK: BCII</span>
            </div>

            <div>
                <h4 class="footer-col-head">Platform</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo esc_url( home_url( '/token/' ) ); ?>">How the Token Works</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/platform/' ) ); ?>">Platform &amp; Technology</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/advertising/' ) ); ?>">Advertising Strategy</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/business-model/' ) ); ?>">Business Model</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-col-head">Company</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About BCII</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/market/' ) ); ?>">Market Opportunity</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/investment/' ) ); ?>">Investment Thesis</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/leadership/' ) ); ?>">Leadership</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-col-head">Investor Relations</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News &amp; Press Releases</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/investor-relations/' ) ); ?>">Live Stock Chart</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/investor-relations/' ) ); ?>">Share Structure</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/investor-relations/' ) ); ?>">Contact IR</a></li>
                </ul>
            </div>
        </div>

        <p class="footer-disclaimer">
            <strong>Forward-Looking Statement:</strong> This website contains forward-looking statements regarding BCII's planned products, strategies, market opportunities, revenue projections, and technological developments. Words such as "planned," "strategy," "positioned to," "anticipates," and "designed to" identify forward-looking statements. Actual results may differ materially due to regulatory developments, market adoption rates, technical implementation challenges, and competitive dynamics. All projections represent potential scenarios under stated assumptions and are not guaranteed forecasts.
        </p>

        <div class="footer-bottom">
            <span class="footer-copyright">© <?php echo esc_html( $year ); ?> BCII Enterprises Inc. All rights reserved. Delaware Corporation.</span>
            <span class="footer-copyright">OTC Pink: BCII · CUSIP: 09368L 100 · SIC: 6099</span>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
