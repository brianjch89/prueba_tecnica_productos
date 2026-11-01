# API RESTful de Catálogo de Productos y Precios Multimoneda

Este proyecto consiste en una API RESTful de alta robustez desarrollada bajo el framework **Laravel 13** y el motor de persistencia **SQLite**. Su objetivo principal es la gestión integral de un catálogo de inventario de productos junto con un subsistema relacional de desglose de precios alternativos en múltiples divisas internacionales.

El sistema destaca por su blindaje perimetral en la entrada de datos, consistencia transaccional y la resolución de comportamientos específicos de bases de datos mediante software.

---

## 1. Manual de Instalación y Configuración

### 🛠️ Requisitos Técnicos del Entorno
- **PHP:** Versión 8.2 o superior (Desarrollado y verificado sobre **PHP 8.5.4**).
- **Gestor de Dependencias:** Composer 2.x (Optimizado bajo **Composer 2.9.3**).
- **Motor de Base de Datos:** SQLite 3 (Módulo nativo de PHP activado).
- **Herramienta de Pruebas:** Postman o utilidades de consumo HTTP equivalentes.

### 🚀 Guía de Despliegue Local

Ejecute la siguiente secuencia de comandos en su terminal para inicializar, configurar y poblar el entorno operativo:

1. **Clonar el repositorio de la aplicación e ingresar al directorio de trabajo:**
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   cd prueba_tecnica_productos
   ```

2. **Instalar las dependencias de desarrollo y producción mediante Composer:**
   ```bash
   composer install
   ```

3. **Configurar el archivo de variables de entorno:**
   Copie el archivo de plantilla predeterminado para habilitar el entorno de configuración local:
   ```bash
   cp .env.example .env
   ```

4. **Inicializar el archivo físico de persistencia SQLite:**
   Cree el archivo vacío que servirá como base de datos relacional para el proyecto:
   ```bash
   touch database/database.sqlite
   ```

5. **Generar la clave criptográfica única de la aplicación:**
   ```bash
   php artisan key:generate
   ```

6. **Ejecutar el set de migraciones y poblar la base de datos (Seeding):**
   Este comando reestructurará el esquema de tablas desde cero (`currencies`, `products`, `products_prices`) e inyectará de forma coherente registros de prueba simulados con Faker PHP (monedas base, artículos y matrices de precios cruzados):
   ```bash
   php artisan migrate:fresh --seed
   ```

7. **Iniciar el servidor local de desarrollo:**
   ```bash
   php artisan serve
   ```
   Los endpoints del servicio quedarán expuestos inmediatamente en la raíz: `http://localhost:8000/api`.

---

## 2. Diccionario de Endpoints de la API

### 📦 Módulo de Productos (`ProductController`)

#### `GET /api/products` (Listar productos con paginación)
- **Descripción:** Recupera la colección completa de productos ordenados alfabéticamente de forma ascendente (A-Z) por el campo `name`. Delega el control de la segmentación de registros directamente al cliente.
- **Parámetros de Consulta (Query Params):**
  - `paginate` *(Obligatorio, entero, valor mínimo: 1)*: Determina la cantidad de registros a renderizar por página.
  - `page` *(Opcional, entero)*: Índice de la página de datos que se desea consultar.
- **Respuestas de Control HTTP:**
  - **`200 OK` (Con registros):** Devuelve el objeto paginador de Laravel dentro de la llave estructural `data`, cargando de forma eficiente la relación con la moneda.
  - **`200 OK` (Sin registros):** Cuando la tabla carece de filas. Retorna un mensaje indicativo y una estructura de datos limpia.
    ```json
    {
        "message": "No se encontraron productos almacenados en el sistema.",
        "data": []
    }
    ```
  - **`400 Bad Request` (Error de rango o formato):** Se dispara si se omite el parámetro `paginate`, si los valores no son numéricos, o si la página solicitada (`page`) supera la última página real calculada (`lastPage`).
    ```json
    {
        "message": "La página solicitada no existe. La última página disponible es la 5."
    }
    ```

#### `POST /api/products` (Crear un nuevo producto)
- **Descripción:** Registra un nuevo artículo en la base de datos evaluando de forma estricta que su nombre sea único a nivel global.
- **Cuerpo de la Petición (JSON Body):**
  - `name` *(string, requerido, máximo 255 caracteres)*.
  - `currency_id` *(integer, requerido, mínimo 1, debe coincidir con una PK válida en `currencies`)*.
  - `price` *(numeric, requerido, mínimo 0, máximo 999999.99, escala máxima de 2 decimales)*.
  - `tax_cost` *(numeric, requerido, mínimo 0, máximo 999999.99, escala máxima de 2 decimales)*.
  - `manufacturing_cost` *(numeric, requerido, mínimo 0, máximo 999999.99, escala máxima de 2 decimales)*.
  - `description` *(string, opcional, admite valores nulos o numéricos gracias al casteo interno del Modelo)*.
- **Respuestas de Control HTTP:**
  - **`201 Created`:** Inserción exitosa. Devuelve un mensaje de confirmación junto al objeto completo del producto persistido.
  - **`400 Bad Request`:** Se genera si se viola alguna regla estructural o si se detecta una colisión por nombre duplicado (evaluación insensible a tildes y mayúsculas).
    ```json
    {
        "message": "Este producto ya se encuentra almacenado."
    }
    ```

#### `GET /api/products/{id}` (Ver un producto específico)
- **Descripción:** Retorna la información detallada de un producto determinado a través de su identificador, inyectando la información de su divisa base.
- **Restricción de Ruta:** El parámetro `{id}` viaja en la URL y es auditado por el componente `Validator` interno para forzar un número entero positivo mayor a cero.
- **Respuestas de Control HTTP:**
  - **`200 OK`:** Búsqueda exitosa. Retorna el modelo estructurado dentro de la propiedad `data`.
  - **`400 Bad Request`:** Si el parámetro provisto en la URL rompe la coherencia del tipo de dato requerido.
    ```json
    {
        "message": "El valor del id debe ser un número entero."
    }
    ```

#### `PUT /api/products/{id}` (Actualizar un producto)
- **Descripción:** Modifica la totalidad de los atributos de un producto existente. Garantiza que el nuevo nombre ingresado no colisione con el de otros registros almacenados.
- **Restricción de Ruta:** ID estrictamente entero positivo mayor a cero gestionado por el validador.
- **Respuestas de Control HTTP:**
  - **`200 OK`:** Actualización exitosa. Retorna un mensaje confirmatorio y el objeto con sus propiedades modificadas en tiempo real bajo la llave `data`.
  - **`400 Bad Request`:** Si los datos del cuerpo rompen las reglas matemáticas del negocio (valores negativos, formatos decimales inválidos o nombres duplicados en otras filas).
    ```json
    {
        "message": "Los valores económicos no pueden ser negativos."
    }
    ```
  - **`404 Not Found`:** Si el identificador ingresado tiene el formato correcto pero no coincide con ningún registro de la tabla.
    ```json
    {
        "message": "El producto que intenta actualizar no existe."
    }
    ```

#### `DELETE /api/products/{id}` (Eliminar un producto)
- **Descripción:** Remueve de forma definitiva un artículo del catálogo, ejecutando un borrado relacional en cascada controlado por software.
- **Respuestas de Control HTTP:**
  - **`200 OK`:** Registro eliminado con éxito.
    ```json
    {
        "message": "¡Producto eliminado exitosamente!"
    }
    ```
  - **`400 Bad Request`:** Si el identificador no cumple con las características de una llave primaria íntegra o si el producto no figura en el sistema.
    ```json
    {
        "message": "El producto solicitado no existe en nuestra base de datos."
    }
    ```
  - **`500 Internal Server Error`:** Si la operación de borrado coordinado a nivel de base de datos experimenta una interrupción imprevista.
    ```json
    {
        "message": "No se pudo realizar la eliminación del producto debido a un error interno en el sistema."
    }
    ```

---

### 💸 Módulo de Precios Multimoneda (`ProductPriceController`)

#### `GET /api/products/{id}/prices` (Ver lista de precios de un producto)
- **Descripción:** Recupera el conjunto de precios alternativos de un producto específico, ordenados alfabéticamente por el código de su símbolo monetario (`symbol`). Requiere paginación obligatoria administrada por el Front-End.
- **Respuestas de Control HTTP:**
  - **`200 OK`:** Operación exitosa. Retorna la colección paginada con las divisas y productos asociados mediante Eager Loading bajo la propiedad `data`.
  - **`400 Bad Request`:** Si el identificador del producto es inválido o si el número de página de la consulta se encuentra fuera del rango real.

#### `POST /api/products/{id}/prices` (Registrar precio alternativo para un producto)
- **Descripción:** Vincula un costo adicional en moneda extranjera a un artículo determinado de la base de datos bajo un **Doble Escudo de Protección**.
- **Cuerpo de la Petición (JSON Body):** `currency_id` (integer) y `price` (numeric).
- **Respuestas de Control HTTP:**
  - **`201 Created`:** Asignación exitosa. Retorna los datos de la nueva tarifa indexada en la llave `data`.
  - **`400 Bad Request`:** Si la divisa elegida ya está asociada al producto en la tabla secundaria, o si coincide exactamente con la moneda base original de nacimiento declarada en la tabla principal.
    ```json
    {
        "message": "No se puede registrar este precio. La divisa seleccionada coincide con la moneda base original del producto."
    }
    ```

---

## 3. Decisiones de Arquitectura e Integridad de Datos

Esta sección expone los fundamentos de ingeniería de software que justifican el diseño técnico implementado en esta API:

#### 🟢 Estandarización de Respuestas a Formato JSON Plano (API Plana)
- **Descripción:** Se tomó la decisión arquitectónica de anular el comportamiento nativo de Laravel que emite arrays de errores multidimensionales (`errors: { campo: [...] }`) ante fallos de validación. En su lugar, el flujo captura la primera excepción mediante `$validator->errors()->first()` y la encapsula directamente en una estructura de string plano bajo la propiedad `"message": "texto"`.
- **Justificación:** Esto unifica las respuestas de éxito y error bajo una misma firma estructural, lo que reduce drásticamente la complejidad del procesamiento de datos en el cliente (Front-End) y optimiza la tolerancia a fallos del sistema.

#### 🟢 Resolución de Limitaciones de SQLite mediante Normalización de Cadenas por Software
- **Descripción:** El motor relacional SQLite posee limitaciones nativas estrictas para gestionar la insensibilidad a mayúsculas, minúsculas y caracteres acentuados (tildes) para alfabetos UTF-8 (como el español) durante sentencias de comparación. Para mitigar esto y cumplir con el principio de interactuar con la base de datos mediante Eloquent sin compilar librerías externas en C, se implementó un mecanismo de normalización dual:
  - Se procesan los strings en PHP mediante `str_replace()` para remover acentos en los datos entrantes.
  - Se inyecta una consulta cruda controlada mediante `whereRaw()` que anida funciones `REPLACE()` de SQL en la columna de la tabla para normalizar temporalmente los datos almacenados al momento de la comparación.
- **Justificación:** Esto blinda a la aplicación contra la duplicidad de registros similares (ej. impedir "Camión" si ya existe "camion") y mantiene un control homogéneo de los datos.

#### 🟢 Mecanismo de Cascada Manual Transaccional (Integridad Referencial)
- **Descripción:** Para elevar la seguridad en el manejo de registros financieros y de inventario, se omitió deliberadamente la instrucción `onDelete('cascade')` a nivel físico en los motores de las migraciones. En consecuencia, el control de la integridad referencial se trasladó de la base de datos hacia la capa de software del controlador.
- **Justificación:** El proceso de borrado coordinado en cascada en el método `destroy()` se encapsuló estrictamente dentro de una transacción atómica mediante `DB::transaction()` soportada por un bloque `try-catch`. Si la consulta de eliminación de la tabla principal (`products`) falla por un bloqueo de concurrencia o un error de E/S, Laravel ejecuta un **Rollback automático** de las filas previamente eliminadas de la tabla pivote (`products_prices`). Esto previene el error crítico de persistencia de dejar registros huérfanos y mitiga excepciones en tiempo de ejecución.

#### 🟢 Abstracción y Casteo de Datos Avanzado (Laravel 13)
- **Descripción:** Con el fin de flexibilizar la entrada de datos en campos de texto extensos sin corromper el esquema de datos, se aprovechó la arquitectura basada en métodos `casts()` integrada en Laravel 13 en el modelo `Product.php`.
- **Justificación:** Al declarar `'description' => 'string'`, el ORM Eloquent intercepta el valor y realiza de manera automática una conversión primitiva (*Type Casting*) a cadena de texto antes de la validación y la inserción. De esta manera, si el Front-End transmite números nativos (ej. `1234` o `8.5`) en el campo descriptivo, el sistema los asimila de forma segura como texto (`"1234"` o `"5.5"`), evitando errores de desajuste de tipos de la base de datos y manteniendo una alta flexibilidad en la interfaz de usuario.

#### 🟢 Validación Meticulosa y Gestión de Excepciones del Motor de Base de Datos
- **Descripción:** Cada método que interactúa con operaciones de escritura o lectura segmentada valida perimetralmente los tipos primitivos entrantes mediante la fachada `Validator`.
- **Justificación:** Al configurar reglas estrictas combinadas con `bail|integer|min:1|exists:...`, la API intercepta las peticiones malformadas o fuera de rango en la memoria de la aplicación, deteniendo el ciclo de vida de la petición de forma temprana. Esto actúa como un escudo que evita que peticiones maliciosas o erróneas lleguen al motor SQLite a disparar excepciones críticas del driver (como un desbordamiento numérico por desajuste de escala decimal(8,2) ante valores que superen `999999.99`, o errores de violación de llave foránea), transformándolos en su lugar en respuestas JSON HTTP amigables con el cliente.
