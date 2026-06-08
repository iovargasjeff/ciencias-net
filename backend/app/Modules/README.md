# Módulos Backend

Cada módulo representa una capacidad de negocio. Los módulos complejos pueden separar:

```text
Application/
Domain/
Infrastructure/
Presentation/
```

Los CRUD simples no deben crear capas vacías. Las dependencias permitidas y la estructura completa están definidas en
`../../docs/architecture/backend.md`.

## Modulos oficiales

- `Auth`
- `Usuarios`
- `Academico`
- `Asistencia`
- `Finanzas`
- `Incidencias`
- `Psicologia`
- `Materiales`
- `Horarios`
- `Comunicados`
- `Notificaciones`
- `Shared`

Todo nuevo feature backend debe ubicarse en uno de estos modulos o proponer primero la actualizacion de
`../../docs/architecture/backend.md`. Las carpetas raiz de Laravel (`app/Models`, `app/Http/Controllers`,
`app/UseCases`, `app/Policies`) son legado transitorio y no deben recibir codigo de dominio nuevo.
