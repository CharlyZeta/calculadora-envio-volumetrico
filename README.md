# 📦 Calculadora de Envío Volumétrico para WooCommerce

![Version](https://img.shields.io/badge/version-2.3.13-blue.svg) ![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-violet.svg)

Este plugin potencia tu tienda WooCommerce añadiendo una capa de inteligencia logística. Calcula automáticamente el peso volumétrico de tus productos y les asigna la **Clase de Envío** más adecuada según reglas personalizables, asegurando que cobres el precio justo por tus envíos.

Además, mejora la experiencia de usuario con una calculadora de costos en tiempo real directamente en la ficha del producto.

## 📸 Capturas de Pantalla

### Calculadora en Frontend
![Calculadora de Envío](/frontend_calculator_modal_1764190353941.png)
*El cliente puede calcular su costo de envío fácilmente seleccionando su ubicación.*

### Panel de Administración
![Panel de Ajustes](/backend_settings_page_1764190369690.png)
*Gestiona reglas con Drag & Drop y visualiza la distribución de tus productos.*

### Asistencia en Edición de Producto
![Recomendación de Envío](/product_editor_recommendation_1764190841984.png)
*El sistema sugiere la clase de envío adecuada según las dimensiones ingresadas.*

### Herramientas y Personalización
![Herramientas y Color](/settings_color_recalc_1764190856882.png)
*Personaliza el color del botón y recalcula masivamente tu catálogo con un clic.*

## ✨ Características Principales

*   **Cálculo Automático:** Determina el peso volumétrico `(Ancho x Alto x Largo) / 10000` al guardar cualquier producto.
*   **Asignación Inteligente:** Asigna automáticamente la Clase de Envío de WooCommerce basada en reglas que tú defines.
*   **Calculadora Frontend:** Modal moderno y responsivo en la página de producto para que los clientes calculen su envío por provincia antes de ir al carrito.
*   **Gestión Visual de Reglas:**
    *   **Drag & Drop:** Reordena tus reglas de envío arrastrando y soltando para definir prioridades.
    *   **Barra de Distribución:** Visualiza gráficamente cuántos productos caen en cada clase de envío.
*   **Herramientas Masivas:** Recalcula las clases de envío para todo tu catálogo con un solo clic.
*   **Asistencia al Administrador:** Muestra sugerencias de clase de envío en tiempo real mientras editas un producto.

## 📋 Requisitos

1.  **WordPress** actualizado.
2.  **WooCommerce** instalado y activo.
3.  **Dimensiones de Producto:** Tus productos deben tener Largo, Ancho y Alto configurados.
4.  **Clases de Envío:** Deben existir en WooCommerce (*Ajustes > Envío > Clases de envío*).
5.  **Zonas de Envío:** Configuradas con sus costos por clase (*Ajustes > Envío > Zonas de envío*). El plugin utilizará las zonas correspondientes al país base de tu tienda.

## 🚀 Instalación y Configuración

### 1. Instalación
1.  Sube el archivo `.zip` desde **Plugins > Añadir nuevo > Subir plugin**.
2.  Activa el plugin.

### 2. Configuración de Reglas
Ve a **WooCommerce > Ajustes de Envío Volumétrico**.

1.  **Crear Reglas:** Haz clic en "Añadir Nueva Regla".
2.  **Definir Rangos:** Establece el peso volumétrico mínimo y máximo.
3.  **Asignar Clase:** Elige la Clase de Envío de WooCommerce correspondiente.
4.  **Priorizar:** Usa el icono de arrastrar (hamburguesa) para ordenar las reglas. El sistema evaluará en orden descendente, pero el orden visual te ayuda a organizar tu lógica.
5.  **Guardar:** No olvides guardar los cambios.

### 3. Personalización
En la misma página de ajustes puedes:
*   **Color del Botón:** Personaliza el color del botón "Calcular costo de envío" para que se adapte a tu tema.
*   **Ver Distribución:** Consulta la barra gráfica al final de la página para ver cómo se están categorizando tus productos.

## 🛠️ Herramientas Avanzadas

### Recálculo Masivo
¿Cambiaste tus reglas o importaste productos nuevos?
1.  Ve a la sección **Herramientas** en la página de ajustes.
2.  Ejecuta **"Recalcular Todos los Productos"**.
3.  Una barra de progreso te indicará el estado. Esto procesará todo tu catálogo en lotes para no sobrecargar el servidor.

### Para Desarrolladores (v2.3.13+)
El código ha sido refactorizado para ser modular y escalable:
*   `includes/core-functions.php`: Lógica de negocio y cálculos.
*   `admin/`: Funciones y vistas del panel de administración.
*   `public/`: Lógica del frontend (scripts, estilos, AJAX).
*   **Hooks:** El plugin utiliza los hooks estándar de WooCommerce (`woocommerce_process_product_meta`, `woocommerce_after_add_to_cart_button`) y propios para facilitar la extensibilidad.

## ❓ Preguntas Frecuentes

**¿Qué fórmula se usa para el peso volumétrico?**
Se utiliza el estándar logístico: `(Ancho x Alto x Largo) / 10000`. Las medidas se toman en centímetros (o la unidad configurada en WooCommerce, ajustando el divisor si es necesario).

**¿El cliente ve el peso volumétrico?**
No, el cliente solo ve el costo de envío final calculado. El peso volumétrico es un dato interno para determinar el costo.

---
**Versión:** 2.3.13
**Licencia:** GPL v2 or later
