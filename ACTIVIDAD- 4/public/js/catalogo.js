import { obtenerJSON } from './api.js';

const $ = (selector) => document.querySelector(selector);

const tabla = $('#grilla tbody');
const resumen = $('#resumen');
const error = $('#error');
const selectorOrden = $('#orden');
const botonAnterior = $('#anterior');
const botonSiguiente = $('#siguiente');
const paginaTexto = $('#pagina');

let paginaActual = 1;
let ultimaPagina = 1;
let controladorGrilla = null;
let temporizadorBusqueda = null;
let controladorSugerencias = null;
let controladorRSS = null;
let seleccionActiva = -1;
let sugerenciasActuales = [];

const campoBuscar = $('#buscar');
const listaSugerencias = $('#sugerencias');
const estadoSugerencias = $('#estado-sugerencias');
const campoCodigo = $('#codigo');
const estadoCodigo = $('#estado-codigo');

function mostrarError(mensaje) {
    error.textContent = mensaje;
    error.hidden = false;
}

function limpiarError() {
    error.textContent = '';
    error.hidden = true;
}

function agregarCelda(fila, valor) {
    const celda = document.createElement('td');
    celda.textContent = String(valor ?? '');
    fila.append(celda);
}

function dibujarProductos(productos) {
    const fragmento = document.createDocumentFragment();

    for (const producto of productos) {
        const fila = document.createElement('tr');

        agregarCelda(fila, producto.codigo);
        agregarCelda(fila, producto.nombre);
        agregarCelda(fila, producto.categoria);
        agregarCelda(fila, producto.precio);
        agregarCelda(fila, producto.stock);

        fragmento.append(fila);
    }

    tabla.replaceChildren(fragmento);
}

async function cargarProductos() {
    controladorGrilla?.abort();
    controladorGrilla = new AbortController();

    const signal = controladorGrilla.signal;

    limpiarError();
    resumen.textContent = 'Cargando productos…';
    $('#grilla').setAttribute('aria-busy', 'true');

     const parametros = new URLSearchParams({
        pagina: String(paginaActual),
        por_pagina: '10',
        orden: selectorOrden.value
    });

    // Aplicar el filtro de búsqueda a la tabla
    const textoBusqueda = campoBuscar.value.trim();

    if (textoBusqueda !== '') {
        parametros.set('q', textoBusqueda);
    }

    try {
        const resultado = await obtenerJSON(
            `/api/v1/productos?${parametros}`,
            signal
        );

        if (signal.aborted) return;

        dibujarProductos(resultado.datos ?? []);

        ultimaPagina = Number(resultado.meta?.ultima_pagina ?? 1);
        paginaActual = Number(resultado.meta?.pagina ?? paginaActual);

        paginaTexto.textContent =
            `Página ${paginaActual} de ${ultimaPagina}`;

        botonAnterior.disabled = paginaActual <= 1;
        botonSiguiente.disabled = paginaActual >= ultimaPagina;

        resumen.textContent =
            `Total de productos: ${resultado.meta?.total ?? resultado.datos.length}`;
    } catch (e) {
        if (e.name === 'AbortError') return;

        tabla.replaceChildren();
        resumen.textContent = 'No se pudieron cargar los productos.';
        mostrarError(e.message);
    } finally {
        if (!signal.aborted) {
            $('#grilla').setAttribute('aria-busy', 'false');
        }
    }
}

botonAnterior.addEventListener('click', () => {
    if (paginaActual > 1) {
        paginaActual--;
        cargarProductos();
    }
});

botonSiguiente.addEventListener('click', () => {
    if (paginaActual < ultimaPagina) {
        paginaActual++;
        cargarProductos();
    }
});

selectorOrden.addEventListener('change', () => {
    paginaActual = 1;
    cargarProductos();
});

function cerrarSugerencias() {
    listaSugerencias.replaceChildren();
    listaSugerencias.hidden = true;
    campoBuscar.setAttribute('aria-expanded', 'false');
    campoBuscar.removeAttribute('aria-activedescendant');
    seleccionActiva = -1;
}

function actualizarSeleccion() {
    const opciones = [...listaSugerencias.querySelectorAll('[role="option"]')];

    opciones.forEach((opcion, indice) => {
        const activa = indice === seleccionActiva;
        opcion.setAttribute('aria-selected', String(activa));

        if (activa) {
            campoBuscar.setAttribute('aria-activedescendant', opcion.id);
            opcion.scrollIntoView({ block: 'nearest' });
        }
    });
}

function dibujarSugerencias(productos) {
    sugerenciasActuales = productos;
    seleccionActiva = -1;

    const fragmento = document.createDocumentFragment();

    productos.forEach((producto, indice) => {
        const opcion = document.createElement('li');

        opcion.id = `sugerencia-${indice}`;
        opcion.setAttribute('role', 'option');
        opcion.setAttribute('aria-selected', 'false');
        opcion.textContent = `${producto.nombre} (${producto.codigo})`;

        opcion.addEventListener('click', () => {
            campoBuscar.value = producto.nombre;
            cerrarSugerencias();
            paginaActual = 1;
            cargarProductos();
        });

        fragmento.append(opcion);
    });

    listaSugerencias.replaceChildren(fragmento);
    listaSugerencias.hidden = productos.length === 0;
    campoBuscar.setAttribute('aria-expanded', String(productos.length > 0));

    estadoSugerencias.textContent = productos.length
        ? `${productos.length} sugerencias disponibles.`
        : 'No se encontraron sugerencias.';
}

campoBuscar.addEventListener('input', () => {
    clearTimeout(temporizadorBusqueda);
    controladorSugerencias?.abort();

    const termino = campoBuscar.value.trim();
    paginaActual = 1;
    cargarProductos();

    if (termino.length < 2) {
        cerrarSugerencias();
        estadoSugerencias.textContent = '';
        return;
    }

    temporizadorBusqueda = setTimeout(async () => {
        controladorSugerencias = new AbortController();

        try {
            const resultado = await obtenerJSON(
                `/api/v1/productos/sugerencias?q=${encodeURIComponent(termino)}`,
                controladorSugerencias.signal
            );

            dibujarSugerencias(resultado.datos ?? []);
        } catch (e) {
            if (e.name !== 'AbortError') {
                cerrarSugerencias();
                estadoSugerencias.textContent = e.message;
            }
        }
    }, 250);
});

campoBuscar.addEventListener('keydown', (evento) => {
    if (listaSugerencias.hidden) return;

    if (evento.key === 'ArrowDown') {
        evento.preventDefault();
        seleccionActiva = Math.min(
            seleccionActiva + 1,
            sugerenciasActuales.length - 1
        );
        actualizarSeleccion();
    } else if (evento.key === 'ArrowUp') {
        evento.preventDefault();
        seleccionActiva = Math.max(0, seleccionActiva - 1);
        actualizarSeleccion();
    } else if (evento.key === 'Enter' && seleccionActiva >= 0) {
        evento.preventDefault();

        const producto = sugerenciasActuales[seleccionActiva];
        if (producto) {
            campoBuscar.value = producto.nombre;
            cerrarSugerencias();
            paginaActual = 1;
            cargarProductos();
        }
    } else if (evento.key === 'Escape') {
        cerrarSugerencias();
    }
});

document.addEventListener('click', (evento) => {
    if (!campoBuscar.contains(evento.target)
        && !listaSugerencias.contains(evento.target)) {
        cerrarSugerencias();
    }
});

let controladorCodigo = null;
let temporizadorCodigo = null;

campoCodigo.addEventListener('input', () => {
    clearTimeout(temporizadorCodigo);
    controladorCodigo?.abort();

    const codigo = campoCodigo.value.trim().toUpperCase();

    if (!/^[A-Z0-9-]+$/.test(codigo)) {
        estadoCodigo.textContent = codigo
            ? 'Usa solo letras, números y guiones.'
            : '';
        return;
    }

    if (codigo.length < 3) {
        estadoCodigo.textContent = 'Escribe al menos 3 caracteres.';
        return;
    }

    temporizadorCodigo = setTimeout(async () => {
        controladorCodigo = new AbortController();

        try {
            const respuesta = await fetch(
                `/api/v1/productos/${encodeURIComponent(codigo)}`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controladorCodigo.signal
                }
            );

            if (respuesta.status === 404) {
                estadoCodigo.textContent = 'Código disponible.';
            } else if (respuesta.ok) {
                estadoCodigo.textContent = 'Este código ya está registrado.';
            } else {
                estadoCodigo.textContent =
                    `No se pudo validar el código (HTTP ${respuesta.status}).`;
            }
        } catch (e) {
            if (e.name !== 'AbortError') {
                estadoCodigo.textContent = 'Error al validar el código.';
            }
        }
    }, 250);
});

async function cargarNoticias() {
    controladorRSS?.abort();
    controladorRSS = new AbortController();

    const lista = $('#noticias');
    const estado = $('#estado-rss');

    estado.textContent = 'Cargando noticias…';

    try {
        const respuesta = await fetch('/noticias.rss', {
            headers: { Accept: 'application/rss+xml, application/xml' },
            signal: controladorRSS.signal
        });

        if (!respuesta.ok) {
            throw new Error(`Error HTTP ${respuesta.status}`);
        }

        const texto = await respuesta.text();
        const xml = new DOMParser().parseFromString(texto, 'application/xml');

        if (xml.querySelector('parsererror')) {
            throw new Error('El RSS recibido no es XML válido.');
        }

        const noticias = [...xml.querySelectorAll('item')].slice(0, 5);
        const fragmento = document.createDocumentFragment();

        for (const noticia of noticias) {
            const elemento = document.createElement('li');
            const titulo = document.createElement('h3');
            const descripcion = document.createElement('p');

            titulo.textContent = noticia.querySelector('title')?.textContent ?? '';
            descripcion.textContent =
                noticia.querySelector('description')?.textContent ?? '';

            elemento.append(titulo, descripcion);
            fragmento.append(elemento);
        }

        lista.replaceChildren(fragmento);
        estado.textContent = `Noticias cargadas: ${noticias.length}.`;
    } catch (e) {
        if (e.name !== 'AbortError') {
            lista.replaceChildren();
            estado.textContent = `No se pudieron cargar las noticias: ${e.message}`;
        }
    }
}

cargarProductos();
cargarNoticias();
