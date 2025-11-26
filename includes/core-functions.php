<?php
/**
 * Funciones del núcleo del plugin.
 *
 * @package Calculadora_Envio_Volumetrico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Función reutilizable que aplica la lógica de cálculo a un solo producto.
 *
 * @param int $product_id El ID del producto.
 * @return bool True si se realizó un cambio, false en caso contrario.
 */
function cev_aplicar_logica_a_producto( $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) return false;

    $width  = $product->get_width();
    $height = $product->get_height();
    $length = $product->get_length();

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
    
    $product->set_shipping_class_id( $shipping_class_id );
    update_post_meta( $product_id, '_peso_volumetrico', round( $peso_volumetrico, 2 ) );
    update_post_meta( $product_id, '_categoria_envio_sugerida', $categoria_nombre );
    $product->save();
    
    return true;
}

/**
 * Retorna el listado de estados/provincias del país base de la tienda.
 */
function cev_get_store_states() {
    $base_country = WC()->countries->get_base_country();
    $states = WC()->countries->get_states( $base_country );
    
    return $states ? $states : [];
}

