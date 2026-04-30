# App de Reclamo de Refrigerio

Esta aplicación PHP permite:
- registrar estudiantes con documento y nombre
- reclamar refrigerio si están registrados
- evitar reclamos adicionales en el mismo día
- guardar la información en una base de datos SQLite

## Archivos

- `index.php`: página principal con formulario de registro y reclamo
- `data/refrigerio.db`: base de datos SQLite creada automáticamente

## Uso

1. Coloca el proyecto en un servidor PHP o ejecuta desde la terminal:

```bash
php -S localhost:8000
```

2. Abre `http://localhost:8000` en el navegador.

3. Registra un estudiante y luego reclama el refrigerio usando su documento.
