#!/bin/bash
# Script para crear respaldos versionados de SPA Manager
# Uso: ./crear_respaldo.sh <numero_respaldo> "Descripción del respaldo"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Validar parámetros
if [ $# -lt 2 ]; then
    echo -e "${RED}❌ Error: Faltan parámetros${NC}"
    echo "Uso: $0 <numero_respaldo> \"Descripción del respaldo\""
    echo "Ejemplo: $0 5 \"Bitácora completa y funcional\""
    exit 1
fi

BACKUP_NUM=$1
DESCRIPCION=$2
VERSION="v1.0"
TAG_NAME="${VERSION}-backup-${BACKUP_NUM}"
FECHA=$(date +"%d/%b/%Y %H:%M")

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}📦 Creando Respaldo #${BACKUP_NUM}${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Tag:${NC} ${TAG_NAME}"
echo -e "${YELLOW}Descripción:${NC} ${DESCRIPCION}"
echo -e "${YELLOW}Fecha:${NC} ${FECHA}"
echo ""

# Verificar si el tag ya existe
if git rev-parse "$TAG_NAME" >/dev/null 2>&1; then
    echo -e "${RED}❌ Error: El tag ${TAG_NAME} ya existe${NC}"
    echo "Usa otro número de respaldo o elimina el tag existente con:"
    echo "git tag -d ${TAG_NAME}"
    exit 1
fi

# Verificar si hay cambios sin commit
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}⚠️  Advertencia: Hay cambios sin commit${NC}"
    echo "¿Deseas hacer commit primero? (s/n)"
    read -r respuesta
    if [ "$respuesta" = "s" ]; then
        echo "Mensaje del commit:"
        read -r commit_msg
        git add .
        git commit -m "$commit_msg"
    fi
fi

# Obtener hash del commit actual
COMMIT_HASH=$(git rev-parse --short HEAD)

# Crear el tag anotado
MENSAJE="🔖 RESPALDO #${BACKUP_NUM}

Descripción: ${DESCRIPCION}
Fecha: ${FECHA}
Commit: ${COMMIT_HASH}

Estado del proyecto:
- Base de datos: spa_manager (MySQL)
- Servidor: VPS Contabo
- URL: https://apps.bitnergia.cl/spa-manager/
- Arquitectura: PHP 8.3 + MySQL 8.0

Para restaurar este respaldo:
git checkout ${TAG_NAME}
"

git tag -a "$TAG_NAME" -m "$MENSAJE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Tag creado exitosamente: ${TAG_NAME}${NC}"
    echo ""
    echo "Información del tag:"
    git show "$TAG_NAME" --no-patch
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}Siguiente paso:${NC}"
    echo "git push origin ${TAG_NAME}"
    echo ""
    echo -e "${YELLOW}Para restaurar este respaldo en el futuro:${NC}"
    echo "git checkout ${TAG_NAME}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
else
    echo -e "${RED}❌ Error al crear el tag${NC}"
    exit 1
fi
