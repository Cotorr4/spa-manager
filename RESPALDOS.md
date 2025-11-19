# 📦 Sistema de Respaldos Versionados - SPA Manager

## 🎯 Objetivo

Este sistema permite crear puntos de restauración numerados y documentados del proyecto SPA Manager, facilitando volver a estados estables en caso de errores o para comparar versiones.

## 🏷️ Formato de Versionado

```
v1.0-backup-X
```

- **v1.0**: Versión mayor del proyecto
- **backup-X**: Número secuencial del respaldo (1, 2, 3, etc.)

## 📋 Scripts Disponibles

### 1. `crear_respaldo.sh` - Crear un nuevo respaldo

**Uso:**
```bash
./crear_respaldo.sh <numero> "Descripción del respaldo"
```

**Ejemplo:**
```bash
./crear_respaldo.sh 5 "Bitácora completa y funcional"
```

**Lo que hace:**
- Verifica que no exista un respaldo con ese número
- Detecta cambios sin commit y pregunta si los quieres guardar
- Crea un tag anotado con información detallada
- Muestra instrucciones para hacer push a GitHub

### 2. `listar_respaldos.sh` - Ver todos los respaldos

**Uso:**
```bash
./listar_respaldos.sh
```

**Lo que muestra:**
- Lista de todos los respaldos creados
- Fecha y commit de cada respaldo
- Marca cuál es el respaldo actual (si aplica)
- Total de respaldos disponibles

### 3. `restaurar_respaldo.sh` - Volver a un respaldo anterior

**Uso:**
```bash
./restaurar_respaldo.sh <numero>
```

**Ejemplo:**
```bash
./restaurar_respaldo.sh 5
```

**Lo que hace:**
- Verifica que el respaldo exista
- Detecta cambios sin guardar y pregunta qué hacer
- Muestra información del respaldo
- Pide confirmación antes de restaurar
- Realiza checkout al tag especificado

## 📚 Historial de Respaldos Recomendado

### Respaldos Iniciales (Retroactivos)

**Respaldo #1 - Base del Proyecto**
```bash
# Identificar commit inicial y crear tag retroactivo
git tag -a v1.0-backup-1 <commit-hash> -m "Implementación inicial del proyecto"
```

**Respaldo #2 - Sistema de Tratamientos**
```bash
git tag -a v1.0-backup-2 <commit-hash> -m "Sistema de tratamientos con fotos"
```

**Respaldo #3 - Calendario y Reservas**
```bash
git tag -a v1.0-backup-3 <commit-hash> -m "Calendario y reservas funcional"
```

**Respaldo #4 - Fichas de Salud**
```bash
git tag -a v1.0-backup-4 <commit-hash> -m "Fichas de salud implementadas"
```

### Respaldo Actual

**Respaldo #5 - Bitácora Completa** ⭐
```bash
./crear_respaldo.sh 5 "Bitácora completa y funcional"
```
- **Commit:** e20ad83
- **Fecha:** 19/Nov/2025 04:00 AM
- **Características:**
  - ✅ CRUD completo de bitácora
  - ✅ Subida de hasta 3 fotos por entrada
  - ✅ Modales con z-index correcto
  - ✅ Preview y eliminación de fotos

## 🔄 Flujos de Trabajo Comunes

### Crear un nuevo respaldo antes de cambios importantes

```bash
# 1. Asegúrate de estar en main con todo commiteado
git status

# 2. Crea el respaldo
./crear_respaldo.sh 6 "Antes de implementar sistema de pagos"

# 3. Sube el tag a GitHub
git push origin v1.0-backup-6

# 4. Continúa con tus cambios
```

### Restaurar un respaldo anterior

```bash
# 1. Ver respaldos disponibles
./listar_respaldos.sh

# 2. Restaurar el respaldo deseado
./restaurar_respaldo.sh 5

# 3. Explorar el código en ese estado
# (Estás en detached HEAD, puedes ver pero no modificar)

# 4. Volver a main cuando termines
git checkout main
```

### Crear una rama desde un respaldo

```bash
# 1. Restaurar el respaldo
./restaurar_respaldo.sh 5

# 2. Crear rama desde aquí
git checkout -b hotfix-desde-backup-5

# 3. Hacer cambios y commits
git add .
git commit -m "fix: Corrección de bug encontrado en backup 5"

# 4. Mergear a main si es necesario
git checkout main
git merge hotfix-desde-backup-5
```

## 🚀 Sincronización con GitHub

### Subir un respaldo específico
```bash
git push origin v1.0-backup-5
```

### Subir todos los respaldos de una vez
```bash
git push origin --tags
```

### Eliminar un respaldo (local)
```bash
git tag -d v1.0-backup-X
```

### Eliminar un respaldo (remoto)
```bash
git push origin --delete v1.0-backup-X
```

## 📝 Registro de Respaldos

### v1.0-backup-5 ⭐ ACTUAL
- **Fecha:** 19/Nov/2025 04:00 AM
- **Commit:** e20ad83
- **Descripción:** Bitácora completa y funcional
- **Características:**
  - Sistema de bitácora con CRUD completo
  - Subida de fotos (max 3 por entrada)
  - Modales con z-index correcto (1000/1100)
  - Preview y eliminación de fotos
  - Vista ampliada de imágenes

### v1.0-backup-4
- **Descripción:** Fichas de salud implementadas
- **Estado:** Pendiente de crear retroactivamente

### v1.0-backup-3
- **Descripción:** Calendario y reservas funcional
- **Estado:** Pendiente de crear retroactivamente

### v1.0-backup-2
- **Descripción:** Sistema de tratamientos con fotos
- **Estado:** Pendiente de crear retroactivamente

### v1.0-backup-1
- **Descripción:** Implementación inicial del proyecto
- **Estado:** Pendiente de crear retroactivamente

## 🎯 Mejores Prácticas

1. **Crear respaldos frecuentes**: Después de completar una funcionalidad importante
2. **Descripciones claras**: Usa descripciones que te ayuden a identificar qué incluye cada respaldo
3. **Sincronizar con GitHub**: Siempre haz push de los tags importantes
4. **Probar antes de respaldar**: Asegúrate de que el código funciona correctamente
5. **Documentar aquí**: Actualiza este README cada vez que crees un respaldo importante

## 🔧 Instalación

```bash
# 1. Copiar scripts al proyecto
cp crear_respaldo.sh /opt/bitnergia/apps-stack/sites/spa-manager/
cp listar_respaldos.sh /opt/bitnergia/apps-stack/sites/spa-manager/
cp restaurar_respaldo.sh /opt/bitnergia/apps-stack/sites/spa-manager/

# 2. Dar permisos de ejecución
chmod +x crear_respaldo.sh listar_respaldos.sh restaurar_respaldo.sh

# 3. Crear el primer respaldo (actual)
./crear_respaldo.sh 5 "Bitácora completa y funcional"

# 4. Subir a GitHub
git push origin v1.0-backup-5
```

## 📖 Referencias

- **Repositorio:** https://github.com/Cotorr4/spa-manager
- **Servidor:** VPS Contabo (srv1037061)
- **URL Producción:** https://apps.bitnergia.cl/spa-manager/

## 🆘 Troubleshooting

### "El tag ya existe"
```bash
# Ver tag existente
git show v1.0-backup-X

# Si quieres reemplazarlo
git tag -d v1.0-backup-X
./crear_respaldo.sh X "Nueva descripción"
```

### "Hay cambios sin commit"
```bash
# Opción 1: Commit de cambios
git add .
git commit -m "feat: Descripción de cambios"

# Opción 2: Stash temporal
git stash
./crear_respaldo.sh X "Descripción"
git stash pop

# Opción 3: Descartar cambios (cuidado)
git reset --hard HEAD
```

### Ver diferencias entre respaldos
```bash
# Comparar backup 4 vs backup 5
git diff v1.0-backup-4 v1.0-backup-5

# Ver archivos cambiados
git diff --name-only v1.0-backup-4 v1.0-backup-5
```

---

**Última actualización:** 19/Nov/2025  
**Mantenedor:** Claudio (@Cotorr4)
