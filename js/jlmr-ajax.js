jQuery(document).ready(function($) {
    // Evento para el botón de búsqueda
    $('#jlmr_buscador').click(function() {
        $('#jlmr_resultados').html('<p>Buscando...</p>'); // Mensaje de búsqueda en progreso

        $.ajax({
            url: jlmrAjax.ajaxurl,
            type: 'post',
            data: {
                action: 'jlmr_buscar',
                nonce: jlmrAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#jlmr_resultados').html('<p>Búsqueda completada. </p><a href="' + response.data.ruta_archivo + '">Descargar Resultados</a>');
                } else {
                    $('#jlmr_resultados').html('<p>No se encontraron resultados.</p>');
                }
            },
            error: function() {
                $('#jlmr_resultados').html('<p>Hubo un error al realizar la búsqueda.</p>');
            }
        });
    });

    // Evento para el botón de duplicar fotos
    $('#jlmr_duplicar_fotos').click(function() {
        $('#jlmr_resultados').html('<p>Duplicando fotos...</p>');

        $.ajax({
            url: jlmrAjax.ajaxurl,
            type: 'post',
            data: {
                action: 'jlmr_duplicar_fotos',
                nonce: jlmrAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#jlmr_resultados').html('<p>' + response.data + '</p>');
                } else {
                    $('#jlmr_resultados').html('<p>Hubo un error al duplicar las fotos.</p>');
                }
            },
            error: function() {
                $('#jlmr_resultados').html('<p>Hubo un error en la solicitud.</p>');
            }
        });
    });
});
