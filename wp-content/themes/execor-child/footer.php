<?php
/**
 * BCII — Child theme footer
 *
 * Logo: SVG inline (no JPEG con fondo blanco que se corta feo sobre el bg
 * oscuro del footer). currentColor permite recolorearlo desde CSS.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$year = date( 'Y' );
?>
</main><!-- /.bcii-main -->

<footer class="bcii-footer">
    <div class="container">
        <div class="footer-cta">
            <div class="footer-cta-text">
                <h3>Ready to dive deeper into the <em>BCII opportunity</em>?</h3>
                <p>Read the full investment thesis or contact our investor relations team.</p>
            </div>
            <div class="footer-cta-actions">
                <a href="<?php echo esc_url( home_url( '/investment/' ) ); ?>" class="btn btn-accent">Investment Thesis</a>
                <a href="<?php echo esc_url( home_url( '/investor-relations/' ) ); ?>" class="btn btn-on-dark">Contact IR</a>
            </div>
        </div>

        <div class="footer-grid">
            <div>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo" aria-label="BCII Enterprises">
                    <span class="footer-brand-text">
                        <span class="name">BCII</span>
                        <span class="sub">Enterprises Inc.</span>
                    </span>
                </a>
                <p class="footer-desc">A blockchain-focused financial technology company headquartered in Vero Beach, Florida. Developing the patent-pending Super Coupon Token platform to transform distribution-list assets into tradeable digital instruments.</p>
                <span class="footer-ticker-badge">OTC ID: BCII</span>
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
            <span class="footer-copyright">© <?php echo esc_html( $year ); ?> BCII Enterprises Inc. All rights reserved. Nevada Corporation.</span>
            <span class="footer-copyright">OTC ID: BCII · CUSIP: 09368L 100 · SIC: 6099</span>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
