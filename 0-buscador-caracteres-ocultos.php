<?php
/**
 * Plugin Name: 000 Buscador de Caracteres Ocultos
 * Plugin URI: https://webyblog.es/
 * Description: Este plugin con el shortcode [jlmr_buscador_caracteres_ocultos] busca en la base de datos de WordPress fotos con el carácter oculto '\u200E' y almacena los nombres de las fotos en un archivo.
 * Version: 03-01-2024
 * Author: Juan Luis Martel
 * Author URI: https://webyblog.es/
 * License: GPL2
 */

// Evitar la ejecución directa del archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente
}



// Función para añadir enlace de Ayuda en el plugin que muestra el fichero ayuda.html
add_filter('plugin_action_links', 'jlmr_agregar_enlace_ayuda_buscador_caracteres_ocultos', 10, 2);

function jlmr_agregar_enlace_ayuda_buscador_caracteres_ocultos($links, $file) {
    // Obtenemos el 'basename' del archivo actual
    $plugin_basename = plugin_basename(__FILE__);
    
    // Comprobamos si estamos en el plugin correcto antes de agregar el enlace
    if ($file == $plugin_basename) {
        // Construimos la URL del archivo de ayuda
        $ayuda_url = plugins_url('ayuda.html', __FILE__);
    
        // Añadimos el nuevo enlace de ayuda al array de enlaces
        $enlace_ayuda = '<a  rel="noopener noreferrer nofollow" href="' . esc_url($ayuda_url) . '" target="_blank">Ayuda</a>';
        array_push($links, $enlace_ayuda);
    }

    return $links;
}



/**
 * Función para registrar el shortcode
 */
function jlmr_register_shortcode() {
    add_shortcode( 'jlmr_buscador_caracteres_ocultos', 'jlmr_render_shortcode' );
}
add_action( 'init', 'jlmr_register_shortcode' );

/**
 * Función para renderizar el shortcode
 */

function jlmr_render_shortcode() {
    // Botones con clases de Unsemantic
    $html = '<div class="jlmr-grid-100">';
    $html .= '<button id="jlmr_buscador" class="button-primary">Buscar Caracteres Ocultos \u200E</button>';
    $html .= '</div>';

    $html .= '<div class="jlmr-grid-100">';
    $html .= '<button id="jlmr_duplicar_fotos" class="button-secondary">Duplicar foto sin carácter oculto</button>';
    $html .= '</div>';

    // Div para mostrar resultados o mensajes con clases de Unsemantic
    $html .= '<div id="jlmr_resultados" class="jlmr-grid-100"></div>';

    // Script necesario para la solicitud AJAX...
    wp_enqueue_script( 'jlmr-ajax-script', plugin_dir_url( __FILE__ ) . 'js/jlmr-ajax.js', array( 'jquery' ) );

    // Pasar variables al script
    wp_localize_script( 'jlmr-ajax-script', 'jlmrAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'jlmr_nonce' )
    ));

    return $html;
}



/**
 * Función para buscar archivos con caracteres ocultos y guardar los resultados
 */
function jlmr_buscar_caracteres_ocultos() {
    global $wpdb;

    // Carácter oculto codificado
    $caracter_oculto = "\xE2\x80\x8E";

    // Preparar la consulta para buscar archivos con las extensiones especificadas y el carácter oculto en el nombre
    $consulta = $wpdb->prepare(
        "SELECT post_title, guid
        FROM $wpdb->posts
        WHERE post_type = 'attachment'
        AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
        AND post_title LIKE %s",
        '%' . $wpdb->esc_like($caracter_oculto) . '%'
    );

    $resultados = $wpdb->get_results($consulta);

    // Nombre del archivo de log actualizado
    $nombre_archivo = 'log-caracteres-ocultos.txt';
    $ruta_archivo = wp_upload_dir()['path'] . '/' . $nombre_archivo; // Ruta local en el servidor

    // Abrir el archivo para escritura
    $archivo = fopen($ruta_archivo, 'w');

    // Escribir los nombres completos de los archivos en el archivo
    foreach ($resultados as $fila) {
        // Extraer el nombre del archivo de la URL
        $nombre_completo_archivo = basename($fila->guid);
        fwrite($archivo, $nombre_completo_archivo . PHP_EOL);
    }

    // Cerrar el archivo
    fclose($archivo);

    // Crear la URL del archivo para la respuesta
    $url_archivo = wp_upload_dir()['url'] . '/' . $nombre_archivo;

    // Devolver la URL del archivo como respuesta
    return $url_archivo;
}

/**
 * Función para buscar fotos, copiar y renombrar sin caracteres ocultos
 */
function jlmr_duplicar_fotos_sin_caracteres_ocultos() {
    global $wpdb;

    // Carácter oculto codificado
    $caracter_oculto = "\xE2\x80\x8E";

    // Consulta para encontrar las fotos
    $consulta = $wpdb->prepare(
        "SELECT post_title, guid
        FROM $wpdb->posts
        WHERE post_type = 'attachment'
        AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
        AND post_title LIKE %s",
        '%' . $wpdb->esc_like($caracter_oculto) . '%'
    );

    $resultados = $wpdb->get_results($consulta);

    // Crear el directorio si no existe
    $directorio_destino = wp_upload_dir()['basedir'] . '/fotos-sin-caracteres-ocultos';
    if (!file_exists($directorio_destino)) {
        wp_mkdir_p($directorio_destino);
    }

    // Copiar y renombrar las fotos
    foreach ($resultados as $fila) {
        $ruta_original = $fila->guid;
        $nombre_archivo_original = basename($ruta_original);

        // Eliminar caracteres ocultos del nombre del archivo
        $nombre_archivo_limpio = str_replace("\u{200E}", '', $nombre_archivo_original);

        // Ruta del archivo de destino
        $ruta_destino = $directorio_destino . '/' . $nombre_archivo_limpio;

        // Copiar el archivo
        if (!file_exists($ruta_destino)) {
            copy($ruta_original, $ruta_destino);
        }
    }

    return 'Fotos duplicadas y limpias de caracteres ocultos.';
}


/**
 * Función para manejar la solicitud AJAX
 */

function jlmr_handle_ajax_request_duplicar() {
    // Verificar el nonce aquí...

    $resultado = jlmr_duplicar_fotos_sin_caracteres_ocultos();

    if (is_wp_error($resultado)) {
        wp_send_json_error($resultado->get_error_message());
    } else {
        wp_send_json_success($resultado);
    }
}


function jlmr_handle_ajax_request() {
    // Verificar el nonce...
    $url_archivo = jlmr_buscar_caracteres_ocultos();

    if (is_wp_error($url_archivo)) {
        wp_send_json_error($url_archivo->get_error_message());
    } else {
        wp_send_json_success(['ruta_archivo' => $url_archivo]);
    }
}
add_action('wp_ajax_jlmr_buscar', 'jlmr_handle_ajax_request');




add_action('wp_ajax_jlmr_duplicar_fotos', 'jlmr_handle_ajax_request_duplicar');

add_action( 'wp_ajax_jlmr_buscar', 'jlmr_handle_ajax_request' );
