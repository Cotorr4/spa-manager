#!/bin/bash
# Script para restaurar un respaldo específico
# Uso: ./restaurar_respaldo.sh <numero_respaldo>

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Validar parámetros
if [ $# -lt 1 ]; then
    echo -e "${RED}❌ Error: Falta el número de respaldo${NC}"
    echo "Uso: $0 <numero_respaldo>"
    echo "Ejemplo: $0 5"
    echo ""
    echo "Respaldos disponibles:"
    ./listar_respaldos.sh
    exit 1
fi

BACKUP_NUM=$1
VERSION="v1.0"
TAG_NAME="${VERSION}-backup-${BACKUP_NUM}"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🔄 Restaurando Respaldo #${BACKUP_NUM}${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Verificar si el tag existe
if ! git rev-parse "$TAG_NAME" >/dev/null 2>&1; then
    echo -e "${RED}❌ Error: El tag ${TAG_NAME} no existe${NC}"
    echo ""
    echo "Respaldos disponibles:"
    ./listar_respaldos.sh
    exit 1
fi

# Verificar si hay cambios sin commit
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}⚠️  ADVERTENCIA: Tienes cambios sin guardar${NC}"
    echo ""
    git status --short
    echo ""
    echo -e "${YELLOW}Opciones:${NC}"
    echo "  1) Guardar cambios en un commit temporal"
    echo "  2) Descartar cambios (PELIGROSO)"
    echo "  3) Cancelar restauración"
    echo ""
    read -p "Elige una opción (1/2/3): " opcion
    
    case $opcion in
        1)
            echo "Ingresa mensaje para commit temporal:"
            read -r commit_msg
            git add .
            git commit -m "temp: ${commit_msg}"
            echo -e "${GREEN}✅ Cambios guardados${NC}"
            ;;
        2)
            git reset --hard HEAD
            git clean -fd
            echo -e "${GREEN}✅ Cambios descartados${NC}"
            ;;
        3)
            echo -e "${YELLOW}Restauración cancelada${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Opción inválida${NC}"
            exit 1
            ;;
    esac
fi

# Mostrar información del respaldo
echo -e "${CYAN}Información del respaldo:${NC}"
git show "$TAG_NAME" --no-patch
echo ""

# Confirmar restauración
echo -e "${YELLOW}¿Confirmas la restauración del respaldo #${BACKUP_NUM}? (s/n)${NC}"
read -r confirmacion

if [ "$confirmacion" != "s" ]; then
    echo -e "${YELLOW}Restauración cancelada${NC}"
    exit 0
fi

# Realizar checkout
git checkout "$TAG_NAME"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Respaldo #${BACKUP_NUM} restaurado exitosamente${NC}"
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}Estado actual:${NC}"
    echo "  Tag: ${TAG_NAME}"
    echo "  Commit: $(git rev-parse --short HEAD)"
    echo ""
    echo -e "${YELLOW}Nota:${NC} Estás en 'detached HEAD' state"
    echo ""
    echo -e "${CYAN}Opciones desde aquí:${NC}"
    echo "  • Explorar este estado: git log, git diff, etc."
    echo "  • Crear rama desde aquí: git checkout -b nueva-rama"
    echo "  • Volver a main: git checkout main"
    echo "  • Aplicar estos cambios a main:"
    echo "    git checkout main"
    echo "    git merge ${TAG_NAME}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
else
    echo -e "${RED}❌ Error al restaurar el respaldo${NC}"
    exit 1
fi
