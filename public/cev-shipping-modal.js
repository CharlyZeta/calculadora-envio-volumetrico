/**
 * Calculadora de Envío Volumétrico - Modal Frontend
 * Maneja la funcionalidad del modal de cálculo de costos de envío
 */

(function() {
    'use strict';

    // Elementos del DOM
    let modal, overlay, closeBtn, selectProvincia, btnCalculate, resultArea, loadingArea;

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initModal();
        attachEventListeners();
    });

    /**
     * Inicializa los elementos del modal
     */
    function initModal() {
        overlay = document.getElementById('cev-modal-overlay');
        modal = document.querySelector('.cev-modal-container');
        closeBtn = document.querySelector('.cev-modal-close');
        selectProvincia = document.getElementById('cev-provincia-select');
        btnCalculate = document.getElementById('cev-btn-calculate');
        resultArea = document.getElementById('cev-result-area');
        loadingArea = document.getElementById('cev-loading');
    }

    /**
     * Adjunta event listeners
     */
    function attachEventListeners() {
        // Botón para abrir el modal
        const triggerBtn = document.querySelector('.cev-shipping-calculator-btn');
        if (triggerBtn) {
            triggerBtn.addEventListener('click', openModal);
        }

        // Botón para cerrar el modal
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        // Cerrar al hacer clic fuera del modal
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeModal();
                }
            });
        }

        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay && overlay.classList.contains('cev-active')) {
                closeModal();
            }
        });

        // Botón de calcular
        if (btnCalculate) {
            btnCalculate.addEventListener('click', calculateShipping);
        }

        // Calcular al cambiar provincia (opcional)
        if (selectProvincia) {
            selectProvincia.addEventListener('change', function() {
                // Limpiar resultados previos al cambiar provincia
                hideResult();
            });
        }
    }

    /**
     * Abre el modal
     */
    function openModal(e) {
        if (e) e.preventDefault();
        if (overlay) {
            overlay.classList.add('cev-active');
            document.body.style.overflow = 'hidden'; // Prevenir scroll del body
        }
    }

    /**
     * Cierra el modal
     */
    function closeModal() {
        if (overlay) {
            overlay.classList.remove('cev-active');
            document.body.style.overflow = ''; // Restaurar scroll
            hideResult();
            hideLoading();
        }
    }

    /**
     * Calcula el costo de envío
     */
    function calculateShipping() {
        const provincia = selectProvincia.value;

        // Validar que se haya seleccionado una provincia
        if (!provincia) {
            showError('Por favor, selecciona una provincia.');
            return;
        }

        // Obtener el ID del producto desde el data attribute o variable global
        const productId = typeof cevShippingData !== 'undefined' ? cevShippingData.productId : null;

        if (!productId) {
            showError('No se pudo obtener el ID del producto.');
            return;
        }

        // Mostrar loading
        showLoading();
        hideResult();

        // Deshabilitar botón
        btnCalculate.disabled = true;

        // Preparar datos para AJAX
        const formData = new FormData();
        formData.append('action', 'cev_calcular_costo_envio');
        formData.append('nonce', cevShippingData.nonce);
        formData.append('product_id', productId);
        formData.append('provincia', provincia);

        // Realizar petición AJAX
        fetch(cevShippingData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            btnCalculate.disabled = false;

            if (data.success) {
                showSuccess(data.data);
            } else {
                showError(data.data.message || 'Ocurrió un error al calcular el costo de envío.');
            }
        })
        .catch(error => {
            hideLoading();
            btnCalculate.disabled = false;
            showError('Error de conexión. Por favor, intenta nuevamente.');
            console.error('Error:', error);
        });
    }

    /**
     * Muestra el resultado exitoso
     */
    function showSuccess(data) {
        if (!resultArea) return;

        resultArea.className = 'cev-result-area cev-show cev-success';
        resultArea.innerHTML = `
            <div class="cev-result-title">Costo de envío estimado</div>
            <div class="cev-result-cost">${data.cost_formatted}</div>
            <div class="cev-result-method">Método: ${data.method_title}</div>
            ${data.delivery_time ? `<div class="cev-result-method">Tiempo estimado: ${data.delivery_time}</div>` : ''}
        `;
    }

    /**
     * Muestra un mensaje de error
     */
    function showError(message) {
        if (!resultArea) return;

        resultArea.className = 'cev-result-area cev-show cev-error';
        resultArea.innerHTML = `
            <div class="cev-result-error">${message}</div>
        `;
    }

    /**
     * Oculta el área de resultados
     */
    function hideResult() {
        if (resultArea) {
            resultArea.className = 'cev-result-area';
            resultArea.innerHTML = '';
        }
    }

    /**
     * Muestra el indicador de carga
     */
    function showLoading() {
        if (loadingArea) {
            loadingArea.classList.add('cev-show');
        }
    }

    /**
     * Oculta el indicador de carga
     */
    function hideLoading() {
        if (loadingArea) {
            loadingArea.classList.remove('cev-show');
        }
    }

})();
