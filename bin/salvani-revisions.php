<?php
/**
 * BCII — Salvani revision markup (10 cambios)
 *
 * Aplica los cambios descritos en BCII_Salvani_Revisions_Markup_1.pdf:
 *   1.  Delaware → Nevada (About, IR, Footer, scan global)
 *   2.  Stated Issuance Value $0.05–$0.10 (Token + Platform)
 *   3.  Transaction Fee 0.3% per side / 0.6% total (Business Model + Platform)
 *   4.  Token Recycling smart contract delivery / expiration (Token Step 5 + Platform)
 *   5.  Platform sold for 60M tokens / 20% (Token Step 1 + Business Model)
 *   6.  CLARITY Act not required + Howey/no-action (Market + Thesis)
 *   7.  Naked Shorts via DTCC (Platform + Home Eight Reasons)
 *   8.  Horizon 60–90 days timeline (Home + About)
 *   9.  5-Year recycle paragraph (Business Model)
 *  10.  Global Delaware → Nevada scan
 *
 * 100% idempotente: cada bloque chequea si el cambio ya se aplicó antes de
 * tocar la base de datos.
 *
 *   docker exec bcii_wordpress wp --allow-root eval-file /var/www/html/bin/salvani-revisions.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ─────────────────────────────────────────────────────────────────────
   Helpers
   ───────────────────────────────────────────────────────────────────── */
function bcii_get_data( $pid ) {
    $raw = get_post_meta( $pid, '_elementor_data', true );
    if ( ! is_string( $raw ) || $raw === '' ) return null;
    $data = json_decode( $raw, true );
    return is_array( $data ) ? $data : null;
}

function bcii_save_data( $pid, $data ) {
    $encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
    update_post_meta( $pid, '_elementor_data', wp_slash( $encoded ) );
    update_post_meta( $pid, '_elementor_version', ELEMENTOR_VERSION );
    delete_post_meta( $pid, '_elementor_css' );
    delete_post_meta( $pid, '_elementor_page_assets' );
}

/** Recorre todos los HTML widgets y aplica un callback que modifica $html.
 * Usa indexación directa porque `($col['elements'] ?? array())` crea una copia
 * y `&$w` modificaría la copia en vez del original. */
function bcii_walk_html_widgets( $pid, callable $cb ) {
    $data = bcii_get_data( $pid );
    if ( ! $data ) return false;
    $changed = false;
    foreach ( $data as $i => $sec ) {
        if ( ! isset( $sec['elements'] ) || ! is_array( $sec['elements'] ) ) continue;
        foreach ( $sec['elements'] as $j => $col ) {
            if ( ! isset( $col['elements'] ) || ! is_array( $col['elements'] ) ) continue;
            foreach ( $col['elements'] as $k => $w ) {
                if ( ( $w['widgetType'] ?? '' ) !== 'html' ) continue;
                $orig = $w['settings']['html'] ?? '';
                $new  = call_user_func( $cb, $orig, $pid, $i );
                if ( is_string( $new ) && $new !== $orig ) {
                    $data[ $i ]['elements'][ $j ]['elements'][ $k ]['settings']['html'] = $new;
                    $changed = true;
                }
            }
        }
    }
    if ( $changed ) bcii_save_data( $pid, $data );
    return $changed;
}

/** Reemplazo simple con idempotencia: si $needle no existe pero $marker (la nueva
 * versión esperada) sí, asume que ya está aplicado. */
function bcii_replace_once( $html, $needle, $replacement ) {
    if ( strpos( $html, $needle ) === false ) return $html;
    return str_replace( $needle, $replacement, $html );
}

/** Inserta $insert después de $anchor (la primera ocurrencia) si $marker no
 * existe ya en el html (idempotencia). */
function bcii_insert_after( $html, $anchor, $insert, $marker = null ) {
    if ( $marker !== null && strpos( $html, $marker ) !== false ) return $html;
    $idx = strpos( $html, $anchor );
    if ( $idx === false ) return $html;
    $cut = $idx + strlen( $anchor );
    return substr( $html, 0, $cut ) . $insert . substr( $html, $cut );
}


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 1 + 10 — Delaware → Nevada (todas las páginas + footer)
   ═════════════════════════════════════════════════════════════════════ */
$pages_to_scan = array( 258, 10008, 10009, 10010, 10011, 10012, 10013, 10014, 10015, 10016, 10017 );
$total_delaware = 0;
foreach ( $pages_to_scan as $pid ) {
    $changed = bcii_walk_html_widgets( $pid, function( $h ) use ( &$total_delaware ) {
        $cnt = substr_count( $h, 'Delaware' );
        if ( $cnt === 0 ) return $h;
        $total_delaware += $cnt;
        return str_replace( 'Delaware', 'Nevada', $h );
    } );
    if ( $changed ) WP_CLI::log( "[1] page $pid — Delaware → Nevada aplicado" );
}
WP_CLI::log( "[1] Total ocurrencias en Elementor data reemplazadas: $total_delaware" );
WP_CLI::log( "[1] Footer.php se cambia aparte (Edit tool en proceso separado)" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 2 — Stated Issuance Value $0.05–$0.10
   ═════════════════════════════════════════════════════════════════════ */
/* 2a) Token page (10009): párrafo nuevo después de "That's the Super Coupon Token." */
$change2_marker_para = 'stated original issuance value of $0.05';
$change2_para = "\n        <p>Each Super Coupon Token carries a stated original issuance value of \$0.05 to \$0.10 per token. Critically, no cash changes hands in the initial distribution — tokens are delivered directly to shareholders and eligible recipients through the existing transfer agent infrastructure. The stated value establishes a recognized baseline for accounting and trading purposes while preserving the zero-cash-outlay design that makes the platform accessible to any issuing organization.</p>";

bcii_walk_html_widgets( 10009, function( $h ) use ( $change2_para, $change2_marker_para ) {
    return bcii_insert_after(
        $h,
        "<p>That's the Super Coupon Token.</p>",
        $change2_para,
        $change2_marker_para
    );
} );
WP_CLI::log( "[2] Token (10009): párrafo Stated Issuance Value insertado" );

/* 2b) Token "at a Glance" spec-grid: agregar Stated Issuance Value después de Settlement */
$change2_spec_token = '            <div class="spec-item"><div class="spec-key">Settlement</div><div class="spec-val">Dollar-backed in-app stablecoin (USD)</div></div>';
$change2_spec_token_new = $change2_spec_token . "\n" . '            <div class="spec-item"><div class="spec-key">Stated Issuance Value</div><div class="spec-val">$0.05–$0.10 per token (no cash required)</div></div>';

bcii_walk_html_widgets( 10009, function( $h ) use ( $change2_spec_token, $change2_spec_token_new ) {
    if ( strpos( $h, 'Stated Issuance Value' ) !== false ) return $h;
    return str_replace( $change2_spec_token, $change2_spec_token_new, $h );
} );
WP_CLI::log( "[2] Token (10009): fila Stated Issuance Value agregada a Token at a Glance" );

/* 2c) Platform (10010) Token Specifications spec-grid */
$change2_spec_plat = '            <div class="spec-item"><div class="spec-key">Settlement</div><div class="spec-val">Dollar-for-dollar-backed in-app stablecoin (USD)</div></div>';
$change2_spec_plat_new = $change2_spec_plat . "\n" . '            <div class="spec-item"><div class="spec-key">Stated Issuance Value</div><div class="spec-val">$0.05–$0.10 per token; no cash required for initial distribution</div></div>';

bcii_walk_html_widgets( 10010, function( $h ) use ( $change2_spec_plat, $change2_spec_plat_new ) {
    if ( strpos( $h, 'Stated Issuance Value' ) !== false ) return $h;
    return str_replace( $change2_spec_plat, $change2_spec_plat_new, $h );
} );
WP_CLI::log( "[2] Platform (10010): fila Stated Issuance Value agregada a Token Specifications" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 3 — Trading Tax 0.3% per side / 0.6% total
   ═════════════════════════════════════════════════════════════════════ */
/* 3a) Business Model (10012) Revenue Stream 03 — reemplazo del párrafo */
$change3_bm_old_p = "<p>BCII plans to earn a percentage of every peer-to-peer trade executed on the platform (proposed at 0.035% of trading volume). Because tokens are tradeable for 10 months within each 11-month cycle, transaction fees are designed to create a perpetual revenue stream.</p>";
$change3_bm_new_p = "<p>BCII receives a trading tax of 0.3% from each of the buyer and seller on every peer-to-peer trade executed on the platform — a total of 0.6% of the transaction value. Because tokens are tradeable for 10 months within each 11-month cycle, this trading tax is designed to create a perpetual, compounding revenue stream that scales with both client count and platform-wide trading volume.</p>";

$change3_bm_old_pill = '<span style="font-family:var(--mono);font-size:0.6rem;color:var(--brand);letter-spacing:0.16em">0.035% PROPOSED TRANSACTION FEE · SCALES WITH VOLUME</span>';
$change3_bm_new_pill = '<span style="font-family:var(--mono);font-size:0.6rem;color:var(--brand);letter-spacing:0.16em">0.3% PER SIDE · 0.6% TOTAL · SCALES WITH VOLUME</span>';

bcii_walk_html_widgets( 10012, function( $h ) use ( $change3_bm_old_p, $change3_bm_new_p, $change3_bm_old_pill, $change3_bm_new_pill ) {
    $h = str_replace( $change3_bm_old_p, $change3_bm_new_p, $h );
    $h = str_replace( $change3_bm_old_pill, $change3_bm_new_pill, $h );
    return $h;
} );
WP_CLI::log( "[3] Business Model: Revenue Stream 03 actualizado a 0.3%/0.6%" );

/* 3b) Platform Token Specifications — agregar Trading Tax (BCII) row */
$change3_plat_anchor = '            <div class="spec-item"><div class="spec-key">Advertising Model</div><div class="spec-val">Zero-cost third-party coupon embedding (planned)</div></div>';
$change3_plat_new = $change3_plat_anchor . "\n" . '            <div class="spec-item"><div class="spec-key">Trading Tax (BCII)</div><div class="spec-val">0.3% per side (buyer + seller) = 0.6% per transaction</div></div>';

bcii_walk_html_widgets( 10010, function( $h ) use ( $change3_plat_anchor, $change3_plat_new ) {
    if ( strpos( $h, 'Trading Tax (BCII)' ) !== false ) return $h;
    return str_replace( $change3_plat_anchor, $change3_plat_new, $h );
} );
WP_CLI::log( "[3] Platform: fila Trading Tax (BCII) agregada" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 4 — Token Recycling smart contract mechanics
   ═════════════════════════════════════════════════════════════════════ */
/* 4a) Token (10009) Step 05 — reemplazar el "Unused tokens cycle back..." */
$change4_old_p = "<p>Unused tokens cycle back to the issuer's treasury, creating natural scarcity and maintaining value. The compounding effect of five distribution cycles creates vesting-like retention mechanics far superior to a one-time dividend.</p>";
$change4_new_p = "<p>At each expiration or exercise event, the Super Coupon Token's smart contract executes one of two outcomes: (1) the embedded smart contract is delivered to the token holder who exercises their coupon, fulfilling the redemption; or (2) the token expires without exercise, triggering an automatic recycle. Only tokens that were distributed during the cycle — or sold by BCII or authorized market makers — recycle back each period to the Company that purchased the platform from BCII. At the end of the full five-year cycle (five separate 11-month periods), all remaining tokens recycle back onto the issuing Company's balance sheet for their decision on deployment, re-issuance, or retirement. This end-of-cycle recapture creates a built-in five-year balance sheet event for every issuing organization.</p>";

bcii_walk_html_widgets( 10009, function( $h ) use ( $change4_old_p, $change4_new_p ) {
    return str_replace( $change4_old_p, $change4_new_p, $h );
} );
WP_CLI::log( "[4] Token (10009): Step 05 párrafo reemplazado" );

/* 4b) Platform 55-Month Cycle — agregar párrafo de detail */
$change4_plat_anchor = '<p>Each token cycles across five distribution rounds, producing vesting-like retention for issuers, transaction-fee revenue, and on-chain proof of consumer awareness planned to replace the opaque impression counts of legacy advertising.</p>';
$change4_plat_new_p = "\n          <p style=\"margin-top:1rem\">Each token cycle concludes with a defined recycling event governed by the platform's smart contract logic. Upon either exercise (redemption by the token holder) or expiration (end of the redemption window), the token resolves automatically: exercised tokens deliver the embedded smart contract offer to the holder; expired tokens recycle to the issuing company. After the full five-year platform lifecycle, all unexercised tokens return to the issuing Company's balance sheet — a structured, predictable recapture event with direct accounting implications under FASB ASU 2023-08.</p>";

bcii_walk_html_widgets( 10010, function( $h ) use ( $change4_plat_anchor, $change4_plat_new_p ) {
    return bcii_insert_after( $h, $change4_plat_anchor, $change4_plat_new_p, 'platform\'s smart contract logic' );
} );
WP_CLI::log( "[4] Platform: 55-Month Cycle párrafo de smart contract logic insertado" );

/* 4c) Platform Token Specifications Breakage Rate row — expandir */
$change4_breakage_old = '            <div class="spec-item"><div class="spec-key">Breakage Rate</div><div class="spec-val">15–25% recycle to issuer treasury after expiration</div></div>';
$change4_breakage_new = '            <div class="spec-item"><div class="spec-key">Breakage / End-of-Cycle Recycle</div><div class="spec-val">Unexercised tokens recycle to issuer treasury per cycle; all unexercised tokens return to issuer balance sheet at end of 5-year lifecycle</div></div>';

bcii_walk_html_widgets( 10010, function( $h ) use ( $change4_breakage_old, $change4_breakage_new ) {
    return str_replace( $change4_breakage_old, $change4_breakage_new, $h );
} );
WP_CLI::log( "[4] Platform: Breakage Rate row expandido" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 5 — Platform sold for 60M tokens (20%)
   ═════════════════════════════════════════════════════════════════════ */
/* 5a) Business Model Revenue Stream 01 — agregar oración */
$change5_bm_anchor = "<p>For every organization that implements the Super Coupon Token, BCII receives approximately 20% of all tokens created. With 300 million tokens per implementation, this translates to approximately 60 million tokens per client held on BCII's balance sheet as digital assets.</p>";
$change5_bm_insert = "\n        <p style=\"margin-top:0.75rem\">Issuing organizations purchase the BCII platform directly using tokens — specifically, 60 million tokens drawn from the 300 million minted per implementation, representing BCII's 20% platform fee. No separate cash payment is required for the platform license; the token allocation constitutes the entire consideration.</p>";

bcii_walk_html_widgets( 10012, function( $h ) use ( $change5_bm_anchor, $change5_bm_insert ) {
    return bcii_insert_after( $h, $change5_bm_anchor, $change5_bm_insert, 'purchase the BCII platform directly using tokens' );
} );
WP_CLI::log( "[5] Business Model: Revenue Stream 01 — oración de compra en tokens insertada" );

/* 5b) Token Step 01 — agregar oración después de "key insight is this..." */
$change5_tok_anchor = "<p>The key insight is this: the distribution list itself is a real asset with real economic value. Until now, it appeared nowhere on any balance sheet. BCII's platform is designed to change that.</p>";
$change5_tok_insert = "\n        <p>The issuing organization acquires the BCII platform by transferring 60 million tokens (20% of the 300 million minted) directly to BCII — no cash payment required. This token-based purchase structure aligns BCII's financial success directly with each issuer's platform performance.</p>";

bcii_walk_html_widgets( 10009, function( $h ) use ( $change5_tok_anchor, $change5_tok_insert ) {
    return bcii_insert_after( $h, $change5_tok_anchor, $change5_tok_insert, 'acquires the BCII platform by transferring 60 million tokens' );
} );
WP_CLI::log( "[5] Token: Step 01 — oración de token-based purchase insertada" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 6 — CLARITY Act + Howey/no-action
   ═════════════════════════════════════════════════════════════════════ */
/* 6a) Market — agregar frase ANTES del párrafo CLARITY existente */
$change6_market_anchor = '<p>Passed the House in July 2025 with a bipartisan vote of 294–134 and is progressing through the Senate with White House support. The bill defines digital commodities, clarifies SEC vs. CFTC jurisdiction, and creates compliance pathways for digital asset businesses.</p>';
$change6_market_insert = '<p style="margin-bottom:1rem"><strong style="color:var(--brand)">BCII\'s platform is not dependent on the passage of the CLARITY Act.</strong> The Act\'s passage would be helpful and is welcomed, but BCII\'s legal position is independently established through existing SEC no-action letter precedents and through the structural design of the Super Coupon Token, which is not expected to be deemed a security under the Howey test.</p>' . "\n        ";

bcii_walk_html_widgets( 10013, function( $h ) use ( $change6_market_anchor, $change6_market_insert ) {
    if ( strpos( $h, 'not dependent on the passage of the CLARITY Act' ) !== false ) return $h;
    return str_replace( $change6_market_anchor, $change6_market_insert . $change6_market_anchor, $h );
} );
WP_CLI::log( "[6] Market: frase 'not dependent on CLARITY Act' agregada" );

/* 6b) Market — agregar subsección Howey/No-Action al final del Regulatory Tailwinds */
$change6_howey_marker = 'SEC No-Action Precedent';
$change6_howey_anchor = '<!-- COMPETITIVE LANDSCAPE -->';
$change6_howey_section = <<<HTML
<!-- HOWEY / NO-ACTION SUBSECTION -->
    <div class="panel" style="margin-bottom:3rem">
      <div class="reg-pill">SEC No-Action Precedent &amp; The Howey Rule</div>
      <h3>Independent Regulatory Foundation</h3>
      <p>A key element of BCII's regulatory position is the Company's analysis demonstrating that the Super Coupon Token fails the Howey test — the SEC's four-part standard for determining whether an instrument constitutes an investment contract (and therefore a security). Because BCII's token functions as a digital coupon with intrinsic redemption value rather than as a passive investment vehicle dependent on the managerial efforts of others, the Company's legal analysis concludes that it does not meet the Howey definition of a security.</p>
      <p style="margin-top:0.75rem">This position is further supported by a series of SEC no-action letters issued to prior blockchain and digital coupon programs — including precedents established on BCII's previous corporate website and documented in the Remergify whitepaper — that confirm the regulatory pathway for coupon-like digital instruments operating outside the securities framework. These no-action letters provide meaningful precedent for BCII's operating model and are incorporated by reference into the Company's regulatory strategy.</p>
      <p style="margin-top:0.75rem;font-size:0.92rem;color:var(--text-dim)">Investors and interested parties are encouraged to review the full no-action letter record available on BCII's prior corporate disclosures and the Remergify whitepaper, both of which contain the relevant SEC correspondence supporting this analysis.</p>
    </div>


HTML;

bcii_walk_html_widgets( 10013, function( $h ) use ( $change6_howey_marker, $change6_howey_anchor, $change6_howey_section ) {
    if ( strpos( $h, $change6_howey_marker ) !== false ) return $h;
    if ( strpos( $h, $change6_howey_anchor ) === false ) return $h;
    return str_replace( $change6_howey_anchor, $change6_howey_section . $change6_howey_anchor, $h );
} );
WP_CLI::log( "[6] Market: subsección Howey/No-Action insertada" );

/* 6c) Thesis Key Risk Factors — reemplazar bullet CLARITY */
$change6_thesis_old = '<li>The CLARITY Act has not yet been signed into law</li>';
$change6_thesis_new = '<li>BCII is not dependent on the CLARITY Act; its passage would be beneficial but is not required for platform operation. The Company\'s regulatory position rests on existing SEC no-action letter precedents and a Howey test analysis that supports the Super Coupon Token\'s non-security status.</li>';

bcii_walk_html_widgets( 10014, function( $h ) use ( $change6_thesis_old, $change6_thesis_new ) {
    return str_replace( $change6_thesis_old, $change6_thesis_new, $h );
} );
WP_CLI::log( "[6] Thesis: bullet CLARITY Act reemplazado" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 7 — Naked Shorts via DTCC (Platform + Home)
   ═════════════════════════════════════════════════════════════════════ */
/* 7a) Platform — insertar nueva sección después de "Five Pillars of Value Creation".
 * Para mantener consistencia con la arquitectura Elementor, agregamos una sección
 * nueva al data array entre la sección Five Pillars (idx 2) y la siguiente. */
function bcii_inject_naked_shorts_section( $pid ) {
    $data = bcii_get_data( $pid );
    if ( ! $data ) return false;
    /* idempotencia */
    foreach ( $data as $sec ) {
        foreach ( ($sec['elements'] ?? array()) as $col ) {
            foreach ( ($col['elements'] ?? array()) as $w ) {
                if ( strpos( wp_json_encode( $w ), 'A Structural Remedy for Naked Short Selling' ) !== false ) return false;
            }
        }
    }
    /* localizar idx de Five Pillars */
    $insert_after = null;
    foreach ( $data as $i => $sec ) {
        foreach ( ($sec['elements'] ?? array()) as $col ) {
            foreach ( ($col['elements'] ?? array()) as $w ) {
                $h = $w['settings']['html'] ?? '';
                if ( strpos( $h, 'Five Pillars of' ) !== false ) { $insert_after = $i; break 3; }
            }
        }
    }
    if ( $insert_after === null ) return false;

    $uid = substr( md5( uniqid('',true) ), 0, 7 );
    $html = <<<'HTML'
<section style="background:var(--bg-alt)">
  <div class="container">
    <div class="label-row"><div class="section-label">Market Structure Impact</div><div class="badge badge-accent">Naked Shorts Remedy</div></div>
    <h2>A Structural Remedy for <em>Naked Short Selling</em></h2>
    <p class="lead" style="margin-bottom:2.5rem">One of the most significant — and underappreciated — consequences of the Super Coupon Token platform is its structural impact on the mechanics of short selling in publicly traded companies that adopt it.</p>

    <div class="two-col" style="align-items:start">
      <div>
        <h3>The Process: How DTCC Share Delivery Works</h3>
        <p>To receive Super Coupon Tokens, shareholders of any participating public company must deliver their shares out of the DTCC system — specifically, out of their brokerage account — and back to the Company's own transfer agent. This is a mandatory, verifiable step in the token distribution process:</p>
        <ul class="arrow-list" style="margin-top:1rem">
          <li>The shareholder initiates a transfer of their shares from their brokerage (held in street name at DTCC) back to the Company's transfer agent (TA).</li>
          <li>Upon the TA receiving and confirming the shares, the TA triggers an API notification to BCII's technology platform.</li>
          <li>The platform then issues Super Coupon Tokens directly into the shareholder's app — authenticated, verified, and tied to confirmed share ownership.</li>
        </ul>
      </div>
      <div>
        <h3>Why This Eliminates Naked Shorts</h3>
        <p>Moving shares from DTCC back to the transfer agent has a profound market-structure consequence: it removes those shares from the pool of securities available for lending, borrowing, and hypothecation at the DTCC level.</p>
        <ul class="check-list" style="margin-top:1rem">
          <li>No longer available to be lent to short sellers without the registered holder's explicit consent.</li>
          <li>Cannot be re-hypothecated or treated as fungible pool assets by broker-dealers.</li>
          <li>Creates a verifiable, on-chain record of true ownership — making detection of phantom share issuance significantly more straightforward.</li>
        </ul>
      </div>
    </div>

    <div class="callout" style="margin-top:2.5rem">
      <p><strong style="color:var(--text)">A Market Structure Advantage for Issuing Companies.</strong> Because shareholders have a direct financial motivation to move their shares, the platform produces consistent, ongoing share delivery out of DTCC on a rolling basis across each distribution cycle. The cumulative effect is a progressively cleaner share registry with a meaningfully reduced float available for short sale activity — entirely legal, market-driven, and shareholder-aligned.</p>
    </div>
  </div>
</section>
HTML;

    $section = array(
        'id'       => $uid,
        'elType'   => 'section',
        'settings' => array(
            'gap'              => 'no',
            'structure'        => '10',
            'content_position' => 'top',
            'padding'          => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
        ),
        'elements' => array(
            array(
                'id'       => substr( md5( uniqid('',true) ), 0, 7 ),
                'elType'   => 'column',
                'settings' => array(
                    '_column_size' => 100,
                    '_inline_size' => null,
                    'padding'      => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
                ),
                'elements' => array(
                    array(
                        'id'         => substr( md5( uniqid('',true) ), 0, 7 ),
                        'elType'     => 'widget',
                        'widgetType' => 'html',
                        'settings'   => array( 'html' => $html ),
                        'elements'   => array(),
                    ),
                ),
                'isInner'  => false,
            ),
        ),
        'isInner' => false,
    );

    array_splice( $data, $insert_after + 1, 0, array( $section ) );
    bcii_save_data( $pid, $data );
    return true;
}
$inj = bcii_inject_naked_shorts_section( 10010 );
WP_CLI::log( "[7] Platform: naked-shorts section " . ( $inj ? 'inyectada' : '(ya existe)' ) );

/* 7b) Home Eight Reasons — agregar bullet */
$change7_home_old = '<li>Positioned within the $2–16T global real-world asset tokenization wave</li>';
$change7_home_new = $change7_home_old . "\n          " . '<li>Platform participation structurally reduces conditions enabling naked short selling — incentivizing shareholders to move shares from DTCC back to the transfer agent on a rolling basis across each distribution cycle</li>';

bcii_walk_html_widgets( 10008, function( $h ) use ( $change7_home_old, $change7_home_new ) {
    if ( strpos( $h, 'structurally reduces conditions enabling naked short selling' ) !== false ) return $h;
    return str_replace( $change7_home_old, $change7_home_new, $h );
} );
WP_CLI::log( "[7] Home: bullet naked-shorts agregado a Eight Reasons" );


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 8 — Horizon 60–90 days timeline
   ═════════════════════════════════════════════════════════════════════ */
/* 8a) Home hero-facts — agregar callout debajo */
$change8_home_anchor = '</div>
    <div class="hero-facts">';
/* En vez de tocar la estructura del hero, agregamos un callout pequeño después
 * del cierre del hero-facts. Buscamos el </div> que cierra .hero-facts. */
$change8_home_callout_marker = 'Platform Technology Update — anticipated within 60';
$change8_home_after = '</div>
    <p class="hero-note" style="margin-top:1.25rem;font-size:0.86rem;color:var(--text-dim);max-width:760px;line-height:1.7">
      <strong style="color:var(--brand)">Platform Technology Update — anticipated within 60–90 days.</strong>
      The Company anticipates that Horizon Globex GmbH will complete the technology build within 60–90 days. Upon successful completion, the Company expects to announce a significant number of public company and private issuer clients over the near term.
    </p>';
/* hook anchor: cerrar la lista de hero-facts y reemplazar */
$change8_home_orig = '</div></section>';  /* probablemente no único — usemos un anchor más específico */
/* Inspect ya mostró hero-facts seguido de </div></section> al final. Para ser seguros buscamos un anchor más único. */
$change8_home_anchor2 = '<div class="btns">
      <a href="/investment/" class="btn btn-ink">View Investment Thesis →</a>';
/* No — usemos la última .hero-fact como anchor para insertar después del </div> que cierra .hero-facts.
 * Strategy más simple: añadir el callout justo antes del </div></section> del hero. */
bcii_walk_html_widgets( 10008, function( $h ) use ( $change8_home_callout_marker, $change8_home_after ) {
    if ( strpos( $h, $change8_home_callout_marker ) !== false ) return $h;
    /* solo en la sección hero (.bcii-hero) */
    if ( strpos( $h, 'class="bcii-hero"' ) === false ) return $h;
    /* localizar fin del .hero-facts y agregar después */
    $anchor = '<div class="hero-facts">';
    $idx = strpos( $h, $anchor );
    if ( $idx === false ) return $h;
    /* contar el </div> de cierre del .hero-facts: buscar matching </div> manual.
     * En el HTML actual hero-facts es <div class="hero-facts"> ... </div></div></section>.
     * Insertamos el callout justo antes de los </div></div></section> finales. */
    return preg_replace(
        '#(</div>\s*</div>\s*</section>)\s*$#s',
        '</div>' . "\n" . substr( $h, 0, 0 ) . '    <p class="hero-note" style="margin-top:1.25rem;font-size:0.86rem;color:var(--text-dim);max-width:760px;line-height:1.7"><strong style="color:var(--brand)">Platform Technology Update — anticipated within 60–90 days.</strong> The Company anticipates that Horizon Globex GmbH will complete the technology build within 60–90 days. Upon successful completion, the Company expects to announce a significant number of public company and private issuer clients over the near term.</p>' . "\n  </div></section>",
        $h, 1
    );
} );
WP_CLI::log( "[8] Home: hero-note de Horizon 60-90 días agregada" );

/* 8b) About — expandir Feb 2026 timeline entry */
$change8_about_old = '<div class="timeline-body">BCII signed a software licensing agreement with Horizon Globex GmbH, becoming sole owner of all token compensation (~20%). CFO Squad Inc. simultaneously issued a favorable FASB ASU 2023-08 accounting opinion. Platform launch anticipated H2 2026.</div>';
$change8_about_new = '<div class="timeline-body">BCII signed a software licensing agreement with Horizon Globex GmbH, becoming sole owner of all token compensation (~20%). CFO Squad Inc. simultaneously issued a favorable FASB ASU 2023-08 accounting opinion. The technology build by Horizon Globex GmbH is anticipated to complete within 60–90 days, after which the Company expects to announce a significant number of public company and private issuer clients. Platform launch anticipated H2 2026.</div>';

bcii_walk_html_widgets( 258, function( $h ) use ( $change8_about_old, $change8_about_new ) {
    if ( strpos( $h, 'technology build by Horizon Globex GmbH is anticipated to complete within 60' ) !== false ) return $h;
    return str_replace( $change8_about_old, $change8_about_new, $h );
} );
WP_CLI::log( "[8] About: timeline Feb 2026 expandida con timeline 60-90 días" );

/* 8c) Recent Developments — crear nuevo press release CPT */
$existing = get_posts( array(
    'post_type'      => 'bcii_pr',
    'title'          => 'Platform Technology Update — Horizon Globex Build Imminent',
    'posts_per_page' => 1,
    'post_status'    => 'any',
) );
if ( empty( $existing ) ) {
    $pr_id = wp_insert_post( array(
        'post_type'    => 'bcii_pr',
        'post_title'   => 'Platform Technology Update — Horizon Globex Build Imminent',
        'post_status'  => 'publish',
        'post_content' => 'Upon successful completion of the technology build by Horizon Globex GmbH — anticipated within 60 to 90 days — the Company expects to announce a significant number of public company and private issuer clients over the near term. Management believes the platform is approaching a pivotal commercialization milestone.',
        'post_excerpt' => 'Upon successful completion of the technology build by Horizon Globex GmbH — anticipated within 60–90 days — the Company expects to announce significant new clients.',
    ) );
    if ( $pr_id && ! is_wp_error( $pr_id ) ) {
        update_post_meta( $pr_id, 'bcii_pr_display_date', '2026-05-13' );
        update_post_meta( $pr_id, 'bcii_pr_tag', 'Platform Update' );
        WP_CLI::log( "[8] Press Release creado #$pr_id" );
    }
} else {
    WP_CLI::log( "[8] Press Release ya existe (#{$existing[0]->ID})" );
}


/* ═════════════════════════════════════════════════════════════════════
   CHANGE 9 — Business Model 5-Year Recycle
   ═════════════════════════════════════════════════════════════════════ */
/* 9a) Revenue Stream 01 — agregar párrafo después del primer párrafo + agregado de 60M anterior. */
$change9_bm_anchor = "<p>For every organization that implements the Super Coupon Token, BCII receives approximately 20% of all tokens created. With 300 million tokens per implementation, this translates to approximately 60 million tokens per client held on BCII's balance sheet as digital assets.</p>";
$change9_bm_insert = "\n        <p style=\"margin-top:0.75rem\">Importantly, the platform is sold to each issuing Company for tokens — not for cash. The issuer conveys 60 million tokens (20% of the 300 million minted) to BCII as the full and complete platform purchase price. At the conclusion of the five-year engagement cycle, all remaining tokens — including those that were not exercised, not traded, or not distributed — recycle back onto the issuing Company's balance sheet. The Company then retains full discretion over how to redeploy, re-issue, or retire those tokens, creating a defined balance-sheet event at the five-year mark.</p>";

bcii_walk_html_widgets( 10012, function( $h ) use ( $change9_bm_anchor, $change9_bm_insert ) {
    return bcii_insert_after( $h, $change9_bm_anchor, $change9_bm_insert, 'the platform is sold to each issuing Company for tokens' );
} );
WP_CLI::log( "[9] Business Model: Revenue Stream 01 — párrafo 5-year recycle insertado" );

/* 9b) Compounding Effect — agregar párrafo de recapture */
$change9_compound_anchor = '<p>Each advertising embed is planned to increase token value across all holdings, creating unrealized gains that flow to BCII\'s net income under FASB fair-value treatment.';
$change9_compound_marker = 'five-year lifecycle concludes with a structured recapture';
$change9_compound_insert = '<p style="margin-top:0.75rem">The five-year lifecycle concludes with a structured recapture: all unexercised and untraded tokens return to the issuing Company\'s balance sheet. For BCII, this creates predictable multi-year token appreciation windows across all active implementations simultaneously. For issuer clients, it creates a defined, contractual endpoint with a tangible balance-sheet outcome — a feature unavailable in any existing dividend, buyback, or loyalty program structure.</p>';

bcii_walk_html_widgets( 10012, function( $h ) use ( $change9_compound_anchor, $change9_compound_insert, $change9_compound_marker ) {
    if ( strpos( $h, $change9_compound_marker ) !== false ) return $h;
    /* anchor parcial — buscamos el </p> que cierra esa frase */
    $idx = strpos( $h, $change9_compound_anchor );
    if ( $idx === false ) return $h;
    /* encontrar el </p> siguiente */
    $end = strpos( $h, '</p>', $idx );
    if ( $end === false ) return $h;
    $cut = $end + 4;
    return substr( $h, 0, $cut ) . "\n        " . $change9_compound_insert . substr( $h, $cut );
} );
WP_CLI::log( "[9] Business Model: Compounding Effect — párrafo recapture insertado" );


/* ═════════════════════════════════════════════════════════════════════
   FINALIZAR — Clear Elementor caches
   ═════════════════════════════════════════════════════════════════════ */
if ( class_exists( 'Elementor\\Plugin' ) ) {
    \Elementor\Plugin::instance()->files_manager->clear_cache();
    WP_CLI::log( "Elementor caches limpiados" );
}

WP_CLI::success( 'Salvani revisions aplicadas' );
