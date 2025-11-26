<?php
/**
 * Funciones de administración del plugin.
 *
 * @package Calculadora_Envio_Volumetrico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Agregar enlace de "Ajustes" en la página de plugins.
 */
add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/calculadora-envio-volumetrico.php' ), 'cev_agregar_enlace_ajustes' );

function cev_agregar_enlace_ajustes( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=cev-ajustes-volumetricos' ) . '">' . __( 'Ajustes', 'calculadora-volumetrico' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

/**
 * Hook que se ejecuta al guardar un producto desde el editor.
 */
add_action( 'woocommerce_process_product_meta', 'cev_guardar_producto_hook', 10, 1 );
function cev_guardar_producto_hook( $product_id ) {
    // Usamos una función intermediaria para evitar conflictos de guardado.
    cev_aplicar_logica_a_producto_desde_guardado($product_id);
}

// Función específica para el hook de guardado que no llama a $product->save()
function cev_aplicar_logica_a_producto_desde_guardado( $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) return;

    // Intentar obtener dimensiones desde $_POST para usar los valores nuevos antes de que se guarden en la BD
    $width  = isset($_POST['_width']) ? wc_format_decimal( wp_unslash( $_POST['_width'] ) ) : $product->get_width();
    $height = isset($_POST['_height']) ? wc_format_decimal( wp_unslash( $_POST['_height'] ) ) : $product->get_height();
    $length = isset($_POST['_length']) ? wc_format_decimal( wp_unslash( $_POST['_length'] ) ) : $product->get_length();

    $peso_volumetrico   = 0;
    $shipping_class_id  = 0;
    $categoria_nombre   = '';

    if ( is_numeric($width) && is_numeric($height) && is_numeric($length) && $width > 0 && $height > 0 && $length > 0 ) {
        $peso_volumetrico = ( (float)$width * (float)$height * (float)$length ) / 10000;
        $escalas = get_option( 'cev_escalas_envio', [] );

        if ( ! empty( $escalas ) ) {
            foreach ( $escalas as $escala ) {
                if ( isset($escala['estado'], $escala['min'], $escala['max'], $escala['class_id']) && $escala['estado'] === 'activo' ) {
                    if ( $peso_volumetrico >= (float)$escala['min'] && $peso_volumetrico <= (float)$escala['max'] ) {
                        $shipping_class_id = (int)$escala['class_id'];
                        $term = get_term($shipping_class_id, 'product_shipping_class');
                        if ($term && !is_wp_error($term)) {
                            $categoria_nombre = $term->name;
                        }
                        break;
                    }
                }
            }
        }
    }

    // Guardar la clase de envío directamente en la base de datos usando términos de taxonomía
    if ( $shipping_class_id > 0 ) {
        wp_set_object_terms( $product_id, array( $shipping_class_id ), 'product_shipping_class' );
    } else {
        wp_set_object_terms( $product_id, array(), 'product_shipping_class' );
    }

    update_post_meta( $product_id, '_peso_volumetrico', round( $peso_volumetrico, 2 ) );
    update_post_meta( $product_id, '_categoria_envio_sugerida', $categoria_nombre );
}

/**
 * Manejadores AJAX para el recálculo masivo.
 */
add_action( 'wp_ajax_cev_iniciar_recalculo', 'cev_iniciar_recalculo_ajax' );
add_action( 'wp_ajax_cev_procesar_lote', 'cev_procesar_lote_ajax' );

function cev_iniciar_recalculo_ajax() {
    check_ajax_referer( 'cev_recalcular_nonce', 'nonce' );

    $query = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);
    $product_ids = $query->posts;

    set_transient( 'cev_recalcular_ids', $product_ids, HOUR_IN_SECONDS );

    wp_send_json_success( ['total' => count( $product_ids )] );
}

function cev_procesar_lote_ajax() {
    check_ajax_referer( 'cev_recalcular_nonce', 'nonce' );

    $product_ids = get_transient( 'cev_recalcular_ids' );

    if ( false === $product_ids ) {
        wp_send_json_error( ['message' => 'Transient expired or not set.'] );
    }

    if ( empty( $product_ids ) ) {
        delete_transient( 'cev_recalcular_ids' );
        wp_send_json_success( ['done' => true] );
    }

    $lote_size = 20;
    $lote_actual = array_slice( $product_ids, 0, $lote_size );

    foreach ( $lote_actual as $product_id ) {
        cev_aplicar_logica_a_producto( $product_id );
    }

    $ids_restantes = array_slice( $product_ids, $lote_size );
    set_transient( 'cev_recalcular_ids', $ids_restantes, HOUR_IN_SECONDS );
    
    wp_send_json_success([
        'remaining' => count($ids_restantes)
    ]);
}

/**
 * Mostrar recomendación de clase de envío en la ficha del producto (Admin).
 */
add_action( 'woocommerce_product_options_shipping', 'cev_mostrar_recomendacion_clase_envio' );

function cev_mostrar_recomendacion_clase_envio() {
    global $post;
    
    $categoria_sugerida = get_post_meta( $post->ID, '_categoria_envio_sugerida', true );
    
    if ( ! $categoria_sugerida ) {
        return;
    }
    
    ?>
    <div id="cev-shipping-recommendation" style="margin: 10px 0 20px; padding: 10px 15px; background-color: #e5f5fa; border-left: 4px solid #00a0d2; color: #333;">
        <p style="margin: 0;">
            <strong><?php _e( 'Recomendación:', 'calculadora-volumetrico' ); ?></strong> 
            <?php printf( __( 'Según las dimensiones, la clase de envío calculada es: <strong>%s</strong>', 'calculadora-volumetrico' ), esc_html( $categoria_sugerida ) ); ?>
        </p>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Mover la recomendación justo antes del selector de clase de envío
            var $selector = $('.product_shipping_class_field');
            if ($selector.length) {
                $('#cev-shipping-recommendation').insertBefore($selector);
            }
        });
    </script>
    <?php
}
