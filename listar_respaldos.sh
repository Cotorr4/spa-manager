#!/bin/bash
# Script para listar todos los respaldos versionados
# Uso: ./listar_respaldos.sh

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}📦 RESPALDOS DISPONIBLES - SPA MANAGER${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Obtener tag actual si estamos en uno
CURRENT_TAG=$(git describe --exact-match --tags 2>/dev/null)

# Listar tags que sean respaldos
BACKUPS=$(git tag -l "v*-backup-*" | sort -V)

if [ -z "$BACKUPS" ]; then
    echo -e "${YELLOW}⚠️  No hay respaldos creados aún${NC}"
    echo ""
    echo "Crea tu primer respaldo con:"
    echo "./crear_respaldo.sh 1 \"Descripción del respaldo\""
    exit 0
fi

# Contador
COUNT=0

# Iterar sobre cada backup
for TAG in $BACKUPS; do
    COUNT=$((COUNT + 1))
    
    # Extraer número de respaldo
    BACKUP_NUM=$(echo "$TAG" | grep -oP 'backup-\K\d+')
    
    # Obtener información del tag
    TAG_DATE=$(git log -1 --format=%ai "$TAG" 2>/dev/null | cut -d' ' -f1)
    TAG_HASH=$(git rev-list -n 1 "$TAG" 2>/dev/null | cut -c1-7)
    TAG_MESSAGE=$(git tag -l --format='%(contents:subject)' "$TAG")
    
    # Marcar si es el tag actual
    if [ "$TAG" = "$CURRENT_TAG" ]; then
        echo -e "${GREEN}➤ ${TAG}${NC} ${CYAN}⭐ (ACTUAL)${NC}"
    else
        echo -e "${YELLOW}  ${TAG}${NC}"
    fi
    
    echo -e "   ${CYAN}Fecha:${NC} ${TAG_DATE}"
    echo -e "   ${CYAN}Commit:${NC} ${TAG_HASH}"
    echo -e "   ${CYAN}Info:${NC} ${TAG_MESSAGE}"
    echo ""
done

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}Total de respaldos:${NC} ${COUNT}"
echo ""
echo -e "${YELLOW}Comandos útiles:${NC}"
echo "  Ver detalles:     git show <tag-name>"
echo "  Restaurar:        git checkout <tag-name>"
echo "  Volver a main:    git checkout main"
echo "  Eliminar tag:     git tag -d <tag-name>"
echo "  Push a GitHub:    git push origin <tag-name>"
echo "  Push todos:       git push origin --tags"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
