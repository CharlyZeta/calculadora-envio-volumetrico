<?php
/**
 * Funciones del frontend del plugin.
 *
 * @package Calculadora_Envio_Volumetrico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrar y encolar scripts y estilos para el frontend.
 */
add_action( 'wp_enqueue_scripts', 'cev_enqueue_frontend_assets' );

function cev_enqueue_frontend_assets() {
    wp_enqueue_style( 'cev-frontend-style', plugin_dir_url( dirname( __FILE__ ) ) . 'public/cev-shipping-modal.css', [], '2.3.14' );

    wp_enqueue_script(
        'cev-shipping-modal',
        plugin_dir_url( dirname( __FILE__ ) ) . 'public/cev-shipping-modal.js',
        ['jquery'],
        '2.3.14',
        true
    );

    // Pasar datos de PHP a JavaScript
    global $post;
    if ( ! $post ) return;

    wp_localize_script(
        'cev-shipping-modal',
        'cevShippingData',
        [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'cev_shipping_nonce' ),
            'productId' => $post->ID,
        ]
    );
    
    // Aplicar color personalizado del botón
    $button_color = get_option( 'cev_button_color', '#f7f7f7' );
    $custom_css = "
        .cev-shipping-calculator-btn {
            background-color: {$button_color} !important;
        }
    ";
    wp_add_inline_style( 'cev-frontend-style', $custom_css );
}

/**
 * Agregar botón y modal en la página del producto.
 */
add_action( 'woocommerce_after_add_to_cart_button', 'cev_render_shipping_calculator' );

function cev_render_shipping_calculator() {
    global $product;
    
    if ( ! $product ) {
        return;
    }

    // Obtener estados/provincias del país de la tienda
    $states = cev_get_store_states();
    $base_country = WC()->countries->get_base_country();
    $country_name = WC()->countries->countries[ $base_country ] ?? 'tu país';
    ?>
    
    <!-- Botón para abrir el modal -->
    <button type="button" class="cev-shipping-calculator-btn">
        Calcular costo de envío
    </button>

    <!-- Modal -->
    <div id="cev-modal-overlay" class="cev-modal-overlay">
        <div class="cev-modal-container">
            <div class="cev-modal-header">
                <h3>Calcular costo de envío</h3>
                <button type="button" class="cev-modal-close" aria-label="Cerrar">&times;</button>
            </div>
            <div class="cev-modal-body">
                <div class="cev-form-group">
                    <label for="cev-provincia-select">Selecciona tu ubicación (<?php echo esc_html($country_name); ?>):</label>
                    <select id="cev-provincia-select" name="provincia">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ( $states as $code => $name ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="button" id="cev-btn-calculate" class="cev-btn-calculate">
                    Calcular
                </button>
                
                <div id="cev-loading" class="cev-loading">
                    <span class="cev-loading-spinner"></span>
                    Calculando...
                </div>
                
                <div id="cev-result-area" class="cev-result-area"></div>
            </div>
        </div>
    </div>
    
    <?php
}

/**
 * Manejador AJAX para calcular el costo de envío.
 */
add_action( 'wp_ajax_cev_calcular_costo_envio', 'cev_calcular_costo_envio_ajax' );
add_action( 'wp_ajax_nopriv_cev_calcular_costo_envio', 'cev_calcular_costo_envio_ajax' );

function cev_calcular_costo_envio_ajax() {
    check_ajax_referer( 'cev_shipping_nonce', 'nonce' );

    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    $provincia  = isset( $_POST['provincia'] ) ? sanitize_text_field( $_POST['provincia'] ) : '';

    if ( ! $product_id || ! $provincia ) {
        wp_send_json_error( ['message' => 'Datos incompletos.'] );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( ['message' => 'Producto no encontrado.'] );
    }

    // Obtener la clase de envío del producto
    $shipping_class_id = $product->get_shipping_class_id();
    
    if ( ! $shipping_class_id ) {
        wp_send_json_error( ['message' => 'Este producto no tiene una clase de envío asignada. Por favor, contacta al vendedor.'] );
    }

    // Obtener zonas de envío de WooCommerce
    $shipping_zones = WC_Shipping_Zones::get_zones();
    
    $costo_encontrado = false;
    $costo = 0;
    $metodo_titulo = '';
    $tiempo_entrega = '';

    // Buscar la zona que incluya la provincia seleccionada
    foreach ( $shipping_zones as $zone ) {
        $zone_obj = new WC_Shipping_Zone( $zone['id'] );
        $locations = $zone_obj->get_zone_locations();
        
        // Verificar si la provincia está en esta zona
        // Verificar si la provincia está en esta zona
        $provincia_en_zona = false;
        $base_country = WC()->countries->get_base_country();

        foreach ( $locations as $location ) {
            // WooCommerce usa códigos de estado formato PAIS:CODIGO (ej: AR:B, US:CA)
            if ( $location->type === 'state' ) {
                // Separar el código de país del código de estado
                $parts = explode( ':', $location->code );
                $loc_country = $parts[0];
                $loc_state   = isset($parts[1]) ? $parts[1] : '';

                // Solo nos interesa si coincide con el país base de la tienda
                if ( $loc_country === $base_country ) {
                    // Comparamos el código de estado enviado (que viene del value del select) con el de la ubicación
                    if ( $loc_state === $provincia ) {
                        $provincia_en_zona = true;
                        break;
                    }
                }
            } else if ( $location->type === 'country' && $location->code === $base_country ) {
                // Si la zona es para todo el país
                $provincia_en_zona = true;
                break;
            }
        }

        if ( ! $provincia_en_zona ) {
            continue;
        }

        // Obtener métodos de envío de esta zona
        $shipping_methods = $zone_obj->get_shipping_methods( true );
        
        foreach ( $shipping_methods as $method ) {
            if ( ! $method->is_enabled() ) {
                continue;
            }

            // Obtener el costo del método
            $method_cost = 0;
            
            // Para Flat Rate, verificar si hay costo por clase de envío
            if ( $method->id === 'flat_rate' ) {
                $shipping_class_costs = $method->get_option( 'class_cost_' . $shipping_class_id, '' );
                
                if ( $shipping_class_costs !== '' ) {
                    $method_cost = floatval( $shipping_class_costs );
                } else {
                    $method_cost = floatval( $method->get_option( 'cost', 0 ) );
                }
            } elseif ( $method->id === 'free_shipping' ) {
                $method_cost = 0;
            } else {
                // Para otros métodos, usar el costo base
                $method_cost = floatval( $method->get_option( 'cost', 0 ) );
            }

            $costo = $method_cost;
            $metodo_titulo = $method->get_title();
            $costo_encontrado = true;
            break;
        }

        if ( $costo_encontrado ) {
            // Verificar si hay un nombre de transporte personalizado para esta clase de envío
            $escalas = get_option( 'cev_escalas_envio', [] );
            foreach ( $escalas as $escala ) {
                if ( $escala['class_id'] == $shipping_class_id && isset($escala['transport_method']) && !empty($escala['transport_method']) ) {
                    $metodo_titulo = $escala['transport_method'];
                    break;
                }
            }
            break;
        }
    }

    if ( ! $costo_encontrado ) {
        wp_send_json_error( ['message' => 'No se encontró un método de envío configurado para ' . esc_html( $provincia ) . '. Por favor, contacta al vendedor.'] );
    }

    // Formatear el costo
    $costo_formateado = wc_price( $costo );

    wp_send_json_success( [
        'cost'           => $costo,
        'cost_formatted' => $costo_formateado,
        'method_title'   => $metodo_titulo,
    ] );
}
