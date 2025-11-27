<?php
/**
 * Plugin Name:       Calculadora de Envío Volumétrico
 * Plugin URI:        https://github.com/gerardomaidana/calculadora-envio-volumetrico
 * Description:       Calcula el peso volumétrico y asigna la Clase de Envío correcta de WooCommerce al guardar el producto. Incluye herramienta de recálculo masivo y calculadora de costos de envío en el frontend.
 * Version:           2.3.14
 * Author:            Gerardo Maidana
 * Author URI:        https://linkedin.com/in/gerardo-maidana
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       calculadora-volumetrico
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

// Incluir archivos del plugin
require_once plugin_dir_path( __FILE__ ) . 'includes/core-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'public/frontend-functions.php';