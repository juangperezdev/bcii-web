<?php

/**
 * Controls attached to core sections
 *
 * @package vamtam/execor
 */

function vamtam_theme_customize_register( $wp_customize ) {
	global $vamtam_theme;

	$wp_customize->add_setting( 'vamtam_theme[wc-product-gallery-zoom]', array(
		'default'           => $vamtam_theme['wc-product-gallery-zoom'],
		'transport'         => 'refresh',
		'type'              => 'option',
		'sanitize_callback' => function ( $input, $setting ) {
			return ( in_array( $input, [ 'enabled', 'disabled' ] ) ) ? $input : $setting->default;
		}
	) );

	$wp_customize->add_control( 'vamtam_theme[wc-product-gallery-zoom]', array(
		'label'    => esc_html__( 'Single Product Image Zoom', 'execor' ),
		'section'  => 'woocommerce_product_images',
		'settings' => 'vamtam_theme[wc-product-gallery-zoom]',
		'type'     => 'radio',
		'choices'  => array(
			'enabled'  => esc_html__( 'Enabled', 'execor' ),
			'disabled' => esc_html__( 'Disabled', 'execor' ),
		),
	) );
}

add_action( 'customize_register', 'vamtam_theme_customize_register' );

