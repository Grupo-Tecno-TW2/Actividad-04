<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de productos | Apicultores</title>
</head>
<body>
    <header>
        <h1>Catálogo de productos</h1>
        <p>Consulta los productos de la asociación.</p>
    </header>

    <main>
        <section aria-labelledby="titulo-catalogo">
            <h2 id="titulo-catalogo">Buscar productos</h2>

            <label for="buscar">Nombre o término de búsqueda</label>
            <input
                id="buscar"
                type="search"
                role="combobox"
                aria-autocomplete="list"
                aria-controls="sugerencias"
                aria-expanded="false"
                autocomplete="off"
            >

            <ul id="sugerencias" role="listbox" hidden></ul>
            <p id="estado-sugerencias" aria-live="polite"></p>
        </section>

        <section aria-labelledby="titulo-grilla">
            <h2 id="titulo-grilla">Productos disponibles</h2>

            <p id="resumen" aria-live="polite">Preparando catálogo…</p>
            <p id="error" role="alert" hidden></p>

            <label for="orden">Ordenar por</label>
            <select id="orden">
                <option value="nombre">Nombre</option>
                <option value="precio">Precio</option>
                <option value="stock">Existencias</option>
            </select>

            <table id="grilla" aria-busy="true">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Producto</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Precio (Bs)</th>
                        <th scope="col">Stock</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <nav aria-label="Paginación del catálogo">
                <button id="anterior" type="button" disabled>Anterior</button>
                <span id="pagina">Página 1</span>
                <button id="siguiente" type="button" disabled>Siguiente</button>
            </nav>
        </section>

        <section aria-labelledby="titulo-validacion">
            <h2 id="titulo-validacion">Consultar código de producto</h2>

            <label for="codigo">Código</label>
            <input id="codigo" name="codigo" autocomplete="off"
                   aria-describedby="estado-codigo">
            <p id="estado-codigo" aria-live="polite"></p>
        </section>

        <section aria-labelledby="titulo-noticias">
            <h2 id="titulo-noticias">Novedades</h2>
            <p id="estado-rss" aria-live="polite">Cargando noticias…</p>
            <ul id="noticias"></ul>
        </section>
    </main>

    <script type="module" src="/js/catalogo.js"></script>
</body>
</html>
