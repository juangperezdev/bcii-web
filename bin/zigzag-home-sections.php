<?php
/**
 * BCII — Aplica el patrón zigzag (alternancia izquierda↔derecha) al home.
 *
 * Antes del script: secciones de texto apiladas verticalmente.
 * Después:
 *   S1  "A New Asset Class"     → text-L  / illustration-R   (+ 3 pillars debajo)
 *   S2  "How It Works"          → steps-L / text-R           (.two-col.is-flipped)
 *   S3  "Why Now Market Context"→ text-L  / 2x2 stats-R
 *   S4  "Eight Reasons"         → panels-L / text-R          (.two-col.is-flipped)
 *
 * Idempotente. Mantiene contenido — solo agrega columnas visuales y flipea.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/zigzag-home-sections.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pid = 10008;
$raw = get_post_meta( $pid, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) {
    WP_CLI::error( 'home (10008) sin _elementor_data' );
}
$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
    WP_CLI::error( 'home _elementor_data inválido' );
}

/* ── URLs de ilustraciones ── */
$img_platform_url = wp_get_attachment_image_url( 10052, 'full' );
$img_platform_alt = 'BCII platform architecture illustration';
if ( ! $img_platform_url ) {
    WP_CLI::error( 'attachment 10052 (platform) no encontrado' );
}

/* ── Helper para reemplazar el HTML de una sección por índice ── */
$set_html = function( &$data, $idx, $new_html ) {
    if ( ! isset( $data[ $idx ]['elements'][0]['elements'][0]['settings']['html'] ) ) {
        return false;
    }
    $data[ $idx ]['elements'][0]['elements'][0]['settings']['html'] = $new_html;
    return true;
};

/* ───────────────────────────────────────────────────────
   Section 1 — "A New Asset Class" (Three Pillars)
   text-L / illustration-R + 3 pillars debajo
   ─────────────────────────────────────────────────────── */
$s1_html = <<<HTML
<section class="bg-white">
  <div class="container">
    <div class="two-col" style="margin-bottom:4rem;">
      <div>
        <div class="label-row">
          <span class="section-label">Platform Overview</span>
          <span class="badge badge-accent">Three Pillars</span>
        </div>
        <h2>A New Asset Class at the Intersection of<br><em>Corporate Finance &amp; Digital Advertising</em></h2>
        <p class="lead">Every organization with a subscriber or customer list is sitting on an invisible asset. BCII's Super Coupon Token is the first platform designed to recognize, monetize, and formally capitalize that asset.</p>
        <div class="btns" style="margin-top:1.5rem;">
          <a href="/platform/" class="btn btn-outline">How the Platform Works →</a>
          <a href="/investment/" class="btn btn-ghost">Investment Thesis</a>
        </div>
      </div>
      <div class="two-col-media">
        <img src="$img_platform_url" alt="$img_platform_alt" loading="lazy" decoding="async">
      </div>
    </div>
    <div class="grid-3">
      <div class="panel">
        <div class="vp-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="5" y="10" width="14" height="4" rx="1"/><rect x="7" y="16" width="10" height="4" rx="1"/><circle cx="12" cy="6" r="0.6" fill="currentColor"/></svg></div>
        <div class="vp-title">A New Asset Class</div>
        <p>CFO Squad's favorable accounting opinion is designed to convert the discount value embedded in each Super Coupon Token into a balance-sheet asset rather than an expense — a distinction with significant implications for public-company earnings under FASB ASU 2023-08.</p>
      </div>
      <div class="panel">
        <div class="vp-icon"><svg viewBox="0 0 24 24"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/><path d="M19 5l-2 2M21 9h-2M19 13l-1-1" opacity="0.5"/></svg></div>
        <div class="vp-title">Zero-Cost Advertising Embedding</div>
        <p>Under BCII's planned advertising strategy, third-party advertisers will embed their coupons into existing tokens for free, paying nothing until redemption. This creates a negative marginal cost of participation that fills the advertiser side of the marketplace rapidly.</p>
      </div>
      <div class="panel">
        <div class="vp-icon"><svg viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 4.5-3.4 8.5-8 9-4.6-.5-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></div>
        <div class="vp-title">Consumer Ownership, Not Surveillance</div>
        <p>In BCII's model, the individual holds the token and benefits directly from its embedded value. The user opts in. Their identity is never disclosed to the advertiser. They are not tracked, profiled, or targeted. The consumer is the beneficiary — not the product.</p>
      </div>
    </div>
  </div>
</section>
HTML;
$set_html( $data, 1, $s1_html );

/* ───────────────────────────────────────────────────────
   Section 2 — "How It Works"
   Toggle .is-flipped en el .two-col existente → steps-L / text-R
   ─────────────────────────────────────────────────────── */
$s2 = $data[2]['elements'][0]['elements'][0]['settings']['html'] ?? '';
if ( $s2 && strpos( $s2, 'two-col is-flipped' ) === false ) {
    $s2 = preg_replace( '/<div class="two-col">/', '<div class="two-col is-flipped">', $s2, 1 );
    $data[2]['elements'][0]['elements'][0]['settings']['html'] = $s2;
}

/* ───────────────────────────────────────────────────────
   Section 3 — "Market Context / Why Now"
   text-L / 2x2 stats-R
   ─────────────────────────────────────────────────────── */
$s3_html = <<<HTML
<section class="bg-alt">
  <div class="container">
    <div class="two-col">
      <div>
        <div class="label-row">
          <span class="section-label">Market Context</span>
          <span class="badge badge-accent">Why Now</span>
        </div>
        <h2>The Convergence Window <em>Is Open</em></h2>
        <p>Real-world asset tokenization is projected to scale from billions today to multi-trillion-dollar volume by 2030. BCII is positioned at the intersection of three structural shifts: programmable digital assets, favorable accounting treatment for tokenized discounts, and a digital ad market hungry for transparent ROI.</p>
        <div class="btns" style="margin-top:1.5rem;">
          <a href="/market/" class="btn btn-outline">Market Deep Dive →</a>
        </div>
      </div>
      <div class="grid-2" style="gap:1.25rem;">
        <div class="panel">
          <div class="stat-num">\$600B+</div>
          <div class="stat-lbl">Global Digital Ad Market</div>
          <div class="stat-desc">40–60% captured by intermediaries before reaching consumers</div>
        </div>
        <div class="panel">
          <div class="stat-num">\$16T</div>
          <div class="stat-lbl">Tokenized Asset Market by 2030</div>
          <div class="stat-desc">BCG projects real-world asset tokenization reaches \$16 trillion</div>
        </div>
        <div class="panel">
          <div class="stat-num">55mo</div>
          <div class="stat-lbl">Token Engagement Cycle</div>
          <div class="stat-desc">Five distribution rounds across 55 months of sustained retention</div>
        </div>
        <div class="panel">
          <div class="stat-num">~20%</div>
          <div class="stat-lbl">BCII Token Allocation</div>
          <div class="stat-desc">Per implementation, held as digital assets at fair value under FASB</div>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;
$set_html( $data, 3, $s3_html );

/* ───────────────────────────────────────────────────────
   Section 4 — "Eight Reasons"
   Toggle .is-flipped en el .two-col existente → panels-L / text-R
   ─────────────────────────────────────────────────────── */
$s4 = $data[4]['elements'][0]['elements'][0]['settings']['html'] ?? '';
if ( $s4 && strpos( $s4, 'two-col is-flipped' ) === false ) {
    $s4 = preg_replace( '/<div class="two-col">/', '<div class="two-col is-flipped">', $s4, 1 );
    $data[4]['elements'][0]['elements'][0]['settings']['html'] = $s4;
}

/* ── Persistir ── */
$encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
update_post_meta( $pid, '_elementor_version', ELEMENTOR_VERSION );
delete_post_meta( $pid, '_elementor_css' );
delete_post_meta( $pid, '_elementor_page_assets' );

WP_CLI::success( "home (10008) → zigzag aplicado (S1: text+img, S2: flipped, S3: text+stats, S4: flipped)" );
