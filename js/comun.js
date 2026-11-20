// Funciones comunes de JavaScript (jQuery). Las usan todas las paginas.

// Escapa un texto para mostrarlo en HTML sin riesgo
function escaparHtml(texto) {
    if (texto === null || texto === undefined) {
        texto = '';
    }
    return $('<div>').text(texto).html().replace(/"/g, '&quot;');
}

// Muestra un mensaje de Bootstrap dentro del elemento. Tipo: success, danger, warning o info
function mostrarMensaje(selector, texto, tipo) {
    $(selector).html('<div class="alert alert-' + tipo + '">' + escaparHtml(texto) + '</div>');
}

// Llena un combo con una lista de objetos {id, texto}. textoInicial es opcional (ej. "Seleccione")
function llenarCombo(selector, lista, textoInicial) {
    var html = '';
    if (textoInicial) {
        html += '<option value="">' + escaparHtml(textoInicial) + '</option>';
    }
    $.each(lista, function (i, item) {
        html += '<option value="' + escaparHtml(item.id) + '">' + escaparHtml(item.texto) + '</option>';
    });
    $(selector).html(html);
}

// Filtro simple de una tabla: oculta las filas que no contienen el texto escrito en la caja
function filtrarTabla(cajaSelector, tablaSelector) {
    $(cajaSelector).on('keyup', function () {
        var texto = $(this).val().toLowerCase();
        $(tablaSelector + ' tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(texto) > -1);
        });
    });
}
