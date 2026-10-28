export async function obtenerJSON(ruta, signal) {
    let respuesta;

    try {
        respuesta = await fetch(ruta, {
            method: 'GET',
            headers: {
                Accept: 'application/json'
            },
            signal
        });
    } catch (error) {
        if (error.name === 'AbortError') {
            throw error;
        }

        throw new Error('No se pudo conectar con el servidor.');
    }

    let contenido;

    try {
        contenido = await respuesta.json();
    } catch {
        throw new Error(`El servidor devolvió una respuesta inválida (HTTP ${respuesta.status}).`);
    }

    if (!respuesta.ok) {
        const mensaje = contenido.detail
            || contenido.error
            || `Error HTTP ${respuesta.status}`;

        throw new Error(mensaje);
    }

    return contenido;
}
