<?php
/**
 * Maneja la página de ajustes del plugin en el panel de administración de WordPress.
 *
 * @package Calculadora_Envio_Volumetrico
 */

// CORRECCIÓN: 'ABSPATH' debe estar en mayúsculas.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Agrega la página de ajustes al menú de WooCommerce.
 */
add_action( 'admin_menu', 'cev_agregar_pagina_ajustes' );

function cev_agregar_pagina_ajustes() {
    add_submenu_page(
        'woocommerce',
        __( 'Ajustes de Envío Volumétrico', 'calculadora-volumetrico' ),
        __( 'Envío Volumétrico', 'calculadora-volumetrico' ),
        'manage_woocommerce',
        'cev-ajustes-volumetricos',
        'cev_renderizar_pagina_ajustes'
    );
}

/**
 * Guarda y actualiza las reglas de envío desde el formulario de ajustes.
 */
function cev_guardar_ajustes() {
    if ( ! isset( $_POST['cev_nonce'] ) || ! wp_verify_nonce( $_POST['cev_nonce'], 'cev_guardar_escalas' ) ) return;
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;

    $escalas_nuevas = [];
    if ( isset( $_POST['cev_escala'] ) && is_array( $_POST['cev_escala'] ) ) {
        foreach ( $_POST['cev_escala'] as $escala_data ) {
            if ( ! empty( $escala_data['class_id'] ) && isset( $escala_data['min'], $escala_data['max'] ) ) {
                $escalas_nuevas[] = [
                    'class_id' => (int) $escala_data['class_id'],
                    'min'      => (float) str_replace(',', '.', $escala_data['min']),
                    'max'      => (float) str_replace(',', '.', $escala_data['max']),
                    'transport_method' => isset( $escala_data['transport_method'] ) ? sanitize_text_field( $escala_data['transport_method'] ) : '',
                    'estado'   => isset( $escala_data['estado'] ) ? 'activo' : 'inactivo',
                ];
            }
        }
    }
    update_option( 'cev_escalas_envio', $escalas_nuevas );
    
    // Guardar lista de métodos de transporte
    if ( isset( $_POST['cev_transport_methods_list'] ) ) {
        update_option( 'cev_transport_methods_list', sanitize_textarea_field( $_POST['cev_transport_methods_list'] ) );
    }
    
    // Guardar color del botón
    if ( isset( $_POST['cev_button_color'] ) ) {
        $button_color = sanitize_hex_color( $_POST['cev_button_color'] );
        update_option( 'cev_button_color', $button_color );
    }
    
    echo '<div class="updated notice is-dismissible"><p>' . __( 'Ajustes guardados correctamente.', 'calculadora-volumetrico' ) . '</p></div>';
}

/**
 * Renderiza el HTML completo de la página de ajustes del plugin.
 */
function cev_renderizar_pagina_ajustes() {
    if ( isset( $_POST['cev_guardar'] ) ) cev_guardar_ajustes();
    
    $escalas = get_option( 'cev_escalas_envio', [] );
    $shipping_classes = get_terms( ['taxonomy' => 'product_shipping_class', 'hide_empty' => false] );
    $button_color = get_option( 'cev_button_color', '#f7f7f7' );
    $transport_methods_list = get_option( 'cev_transport_methods_list', '' );
    
    // Procesar lista de métodos para el dropdown
    $available_methods = array_filter(array_map('trim', explode(',', $transport_methods_list)));
    
    // Encolar el color picker de WordPress y jQuery UI Sortable
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_script( 'jquery-ui-sortable' );
    ?>
    <div class="wrap">
        <h1><?php _e( 'Ajustes de Envío Volumétrico', 'calculadora-volumetrico' ); ?></h1>
        <p><?php _e( 'Asigna una Clase de Envío de WooCommerce a un rango de Peso Volumétrico (PV). Arrastra las filas para reordenar la prioridad.', 'calculadora-volumetrico' ); ?></p>
        
        <form method="post" action="">
            <table class="wp-list-table widefat fixed striped" id="cev-tabla-escalas">
                <thead>
                    <tr>
                        <th style="width: 30px;"><span class="dashicons dashicons-menu" title="<?php _e( 'Orden', 'calculadora-volumetrico' ); ?>"></span></th>
                        <th style="width:5%;"><?php _e( 'Activo', 'calculadora-volumetrico' ); ?></th>
                        <th><?php _e( 'Clase de Envío', 'calculadora-volumetrico' ); ?></th>
                        <th><?php _e( 'Método de Transporte', 'calculadora-volumetrico' ); ?></th>
                        <th><?php _e( 'PV Mínimo', 'calculadora-volumetrico' ); ?></th>
                        <th><?php _e( 'PV Máximo', 'calculadora-volumetrico' ); ?></th>
                        <th style="width:10%;"><?php _e( 'Acción', 'calculadora-volumetrico' ); ?></th>
                    </tr>
                </thead>
                <tbody id="cev-escalas-body">
                    <?php if ( ! empty( $escalas ) ) : foreach ( $escalas as $key => $escala ) : ?>
                    <tr class="cev-escala-fila">
                        <td style="cursor: move;" class="cev-drag-handle"><span class="dashicons dashicons-menu"></span></td>
                        <td><input type="checkbox" name="cev_escala[<?php echo $key; ?>][estado]" <?php checked( $escala['estado'], 'activo' ); ?>></td>
                        <td>
                            <select name="cev_escala[<?php echo $key; ?>][class_id]" class="large-text" required>
                                <option value=""><?php _e( '— Seleccionar —', 'calculadora-volumetrico' ); ?></option>
                                <?php foreach ($shipping_classes as $class) : ?>
                                    <option value="<?php echo esc_attr($class->term_id); ?>" <?php selected($escala['class_id'], $class->term_id); ?>><?php echo esc_html($class->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="cev_escala[<?php echo $key; ?>][transport_method]" class="large-text">
                                <option value=""><?php _e( '— Por defecto —', 'calculadora-volumetrico' ); ?></option>
                                <?php foreach ($available_methods as $method) : ?>
                                    <option value="<?php echo esc_attr($method); ?>" <?php selected(isset($escala['transport_method']) ? $escala['transport_method'] : '', $method); ?>><?php echo esc_html($method); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="cev_escala[<?php echo $key; ?>][min]" value="<?php echo esc_attr( $escala['min'] ); ?>" class="small-text" required></td>
                        <td><input type="number" step="0.01" name="cev_escala[<?php echo $key; ?>][max]" value="<?php echo esc_attr( $escala['max'] ); ?>" class="small-text" required></td>
                        <td><button type="button" class="button button-secondary cev-eliminar-fila"><?php _e( 'Eliminar', 'calculadora-volumetrico' ); ?></button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot><tr><td colspan="7"><button type="button" id="cev-agregar-fila" class="button button-primary"><?php _e( 'Añadir Nueva Regla', 'calculadora-volumetrico' ); ?></button></td></tr></tfoot>
            </table>
            
            <hr>
            
            <h2><?php _e( 'Personalización', 'calculadora-volumetrico' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cev_transport_methods_list"><?php _e( 'Lista de Métodos de Transporte', 'calculadora-volumetrico' ); ?></label>
                    </th>
                    <td>
                        <textarea id="cev_transport_methods_list" name="cev_transport_methods_list" class="large-text" rows="3"><?php echo esc_textarea( $transport_methods_list ); ?></textarea>
                        <p class="description"><?php _e( 'Ingresa los métodos de transporte disponibles separados por comas (ej: Moto, Camión, Correo). Estos aparecerán en el selector de arriba.', 'calculadora-volumetrico' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cev_button_color"><?php _e( 'Color del Botón', 'calculadora-volumetrico' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cev_button_color" name="cev_button_color" value="<?php echo esc_attr( $button_color ); ?>" class="cev-color-picker" />
                        <p class="description"><?php _e( 'Selecciona el color de fondo del botón "Calcular costo de envío" para que coincida con tu tema.', 'calculadora-volumetrico' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php wp_nonce_field( 'cev_guardar_escalas', 'cev_nonce' ); ?>
            <p class="submit"><input type="submit" name="cev_guardar" class="button button-primary" value="<?php _e( 'Guardar Cambios', 'calculadora-volumetrico' ); ?>"></p>
        </form>

        <hr>

        <!-- Barra de Distribución de Productos -->
        <?php
        // Obtener todas las clases de envío y sus conteos
        $all_shipping_classes = get_terms( array(
            'taxonomy' => 'product_shipping_class',
            'hide_empty' => false,
        ) );

        $total_products_assigned = 0;
        $distribution_data = [];
        
        // Paleta de colores suaves
        $colors = ['#4ab866', '#f0ad4e', '#d9534f', '#5bc0de', '#5e6977', '#8e44ad', '#e67e22', '#16a085'];
        $color_index = 0;

        if ( ! empty( $all_shipping_classes ) && ! is_wp_error( $all_shipping_classes ) ) {
            foreach ( $all_shipping_classes as $term ) {
                if ( $term->count > 0 ) {
                    $total_products_assigned += $term->count;
                    $distribution_data[] = [
                        'name' => $term->name,
                        'count' => $term->count,
                        'color' => $colors[ $color_index % count($colors) ]
                    ];
                    $color_index++;
                }
            }
        }
        
        // Ordenar por cantidad descendente para mejor visualización
        usort($distribution_data, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        ?>
        
        <h2><?php _e( 'Distribución de Productos', 'calculadora-volumetrico' ); ?></h2>
        <div class="cev-distribution-container" style="margin-bottom: 30px; background: #fff; padding: 15px; border: 1px solid #ccc;">
            <?php if ( $total_products_assigned > 0 ) : ?>
                <p><?php printf( __( 'Total de productos con clase de envío asignada: <strong>%d</strong>', 'calculadora-volumetrico' ), $total_products_assigned ); ?></p>
                
                <div class="cev-distribution-bar" style="display: flex; width: 100%; height: 30px; border-radius: 4px; overflow: hidden; background: #f0f0f1;">
                    <?php foreach ( $distribution_data as $data ) : 
                        $percent = ($data['count'] / $total_products_assigned) * 100;
                        // Mostrar número solo si el porcentaje es suficiente (> 3%)
                        $show_number = $percent > 3;
                    ?>
                        <div class="cev-bar-segment" style="width: <?php echo esc_attr( $percent ); ?>%; background-color: <?php echo esc_attr( $data['color'] ); ?>; position: relative; transition: all 0.2s;" title="<?php echo esc_attr( $data['name'] . ': ' . $data['count'] . ' productos (' . round($percent, 1) . '%)' ); ?>">
                            <?php if ( $show_number ) : ?>
                                <span style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); color: rgba(255,255,255,0.9); font-size: 11px; font-weight: 600; white-space: nowrap; pointer-events: none; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                                    <?php echo esc_html( $data['count'] ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cev-legend" style="display: flex; flex-wrap: wrap; margin-top: 10px; gap: 10px;">
                    <?php foreach ( $distribution_data as $data ) : ?>
                        <div style="display: flex; align-items: center; font-size: 12px;">
                            <span style="display: inline-block; width: 10px; height: 10px; background-color: <?php echo esc_attr( $data['color'] ); ?>; margin-right: 5px; border-radius: 2px;"></span>
                            <strong><?php echo esc_html( $data['name'] ); ?></strong>: <?php echo esc_html( $data['count'] ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php _e( 'No hay productos con clases de envío asignadas aún.', 'calculadora-volumetrico' ); ?></p>
            <?php endif; ?>
        </div>

        <hr>

        <h2><?php _e( 'Herramientas', 'calculadora-volumetrico' ); ?></h2>
        <div id="cev-herramientas-wrapper" style="border: 1px solid #ccc; padding: 15px; background: #fff;">
            <h4><?php _e( 'Recalcular Clases de Envío', 'calculadora-volumetrico' ); ?></h4>
            <p><?php _e( 'Usa esta herramienta para aplicar la lógica de cálculo a todos tus productos existentes. Esto es útil después de la configuración inicial o si cambias las reglas de envío.', 'calculadora-volumetrico' ); ?></p>
            <button id="cev-recalcular-todos" class="button button-secondary"><?php _e( 'Recalcular Todos los Productos', 'calculadora-volumetrico' ); ?></button>
            <div id="cev-recalcular-status" style="margin-top: 15px; font-family: monospace;"></div>
        </div>
    </div>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar color picker
        jQuery('.cev-color-picker').wpColorPicker();
        
        // Inicializar sortable
        jQuery('#cev-escalas-body').sortable({
            handle: '.cev-drag-handle',
            placeholder: 'ui-state-highlight',
            helper: function(e, ui) {
                ui.children().each(function() {
                    jQuery(this).width(jQuery(this).width());
                });
                return ui;
            }
        });

        const tablaBody = document.getElementById('cev-escalas-body');
        const btnAgregar = document.getElementById('cev-agregar-fila');

        tablaBody.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('cev-eliminar-fila')) {
                e.preventDefault();
                e.target.closest('tr').remove();
            }
        });

        btnAgregar.addEventListener('click', function() {
            const rowCount = Date.now();
            const newRowHTML = `
                <tr class="cev-escala-fila">
                    <td style="cursor: move;" class="cev-drag-handle"><span class="dashicons dashicons-menu"></span></td>
                    <td><input type="checkbox" name="cev_escala[${rowCount}][estado]" checked></td>
                    <td>
                        <select name="cev_escala[${rowCount}][class_id]" class="large-text" required>
                            <option value=""><?php _e( '— Seleccionar una clase —', 'calculadora-volumetrico' ); ?></option>
                            <?php foreach ($shipping_classes as $class) : ?>
                                <option value="<?php echo esc_attr($class->term_id); ?>"><?php echo esc_html($class->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="cev_escala[${rowCount}][transport_method]" class="large-text">
                            <option value=""><?php _e( '— Por defecto —', 'calculadora-volumetrico' ); ?></option>
                            <?php foreach ($available_methods as $method) : ?>
                                <option value="<?php echo esc_attr($method); ?>"><?php echo esc_html($method); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="cev_escala[${rowCount}][min]" class="small-text" required></td>
                    <td><input type="number" step="0.01" name="cev_escala[${rowCount}][max]" class="small-text" required></td>
                    <td><button type="button" class="button button-secondary cev-eliminar-fila"><?php _e( 'Eliminar', 'calculadora-volumetrico' ); ?></button></td>
                </tr>`;
            tablaBody.insertAdjacentHTML('beforeend', newRowHTML);
        });

        const btnRecalcular = document.getElementById('cev-recalcular-todos');
        const statusDiv = document.getElementById('cev-recalcular-status');
        let totalProductos = 0;

        btnRecalcular.addEventListener('click', function() {
            if (!confirm('<?php _e( '¿Estás seguro de que deseas recalcular las clases de envío para TODOS tus productos? Esta acción no se puede deshacer.', 'calculadora-volumetrico' ); ?>')) {
                return;
            }

            this.disabled = true;
            statusDiv.innerHTML = '<p><?php _e( 'Iniciando...', 'calculadora-volumetrico' ); ?></p>';

            const data = new URLSearchParams();
            data.append('action', 'cev_iniciar_recalculo');
            data.append('nonce', '<?php echo wp_create_nonce( 'cev_recalcular_nonce' ); ?>');

            fetch(ajaxurl, { method: 'POST', body: data })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        totalProductos = response.data.total;
                        if (totalProductos === 0) {
                            statusDiv.innerHTML = '<p><?php _e( 'No se encontraron productos para procesar.', 'calculadora-volumetrico' ); ?></p>';
                            btnRecalcular.disabled = false;
                            return;
                        }
                        statusDiv.innerHTML = `<p>0 / ${totalProductos} <?php _e( 'productos procesados.', 'calculadora-volumetrico' ); ?></p><progress id="cev-progreso" max="${totalProductos}" value="0" style="width: 100%;"></progress>`;
                        procesarLote();
                    } else {
                        statusDiv.innerHTML = '<p style="color:red;"><?php _e( 'Error al iniciar.', 'calculadora-volumetrico' ); ?></p>';
                        btnRecalcular.disabled = false;
                    }
                });
        });

        function procesarLote() {
            const data = new URLSearchParams();
            data.append('action', 'cev_procesar_lote');
            data.append('nonce', '<?php echo wp_create_nonce( 'cev_recalcular_nonce' ); ?>');

            fetch(ajaxurl, { method: 'POST', body: data })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        if (response.data.done) {
                            statusDiv.innerHTML = `<p style="color:green;"><b><?php _e( '¡Completado!', 'calculadora-volumetrico' ); ?></b> ${totalProductos} <?php _e( 'productos fueron procesados.', 'calculadora-volumetrico' ); ?></p>`;
                            document.getElementById('cev-progreso').value = totalProductos;
                            btnRecalcular.disabled = false;
                            return;
                        }
                        
                        const restantes = response.data.remaining;
                        const procesados = totalProductos - restantes;
                        document.getElementById('cev-progreso').value = procesados;
                        statusDiv.querySelector('p').innerText = `${procesados} / ${totalProductos} <?php _e( 'productos procesados.', 'calculadora-volumetrico' ); ?>`;

                        procesarLote();
                    } else {
                         statusDiv.innerHTML += '<p style="color:red;"><?php _e( 'Error en el servidor.', 'calculadora-volumetrico' ); ?></p>';
                         btnRecalcular.disabled = false;
                    }
                });
        }
    });
    </script>
    <?php
}