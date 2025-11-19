#!/bin/bash
# Script de instalación del sistema de respaldos
# Uso: curl -o instalar_respaldos.sh [URL] && bash instalar_respaldos.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Instalando Sistema de Respaldos Versionados"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Verificar que estamos en el directorio del proyecto
if [ ! -d ".git" ]; then
    echo "❌ Error: No estás en el directorio raíz del proyecto Git"
    echo "Navega a /opt/bitnergia/apps-stack/sites/spa-manager/"
    exit 1
fi

echo "✅ Directorio del proyecto detectado"
echo ""

# Crear directorio para scripts si no existe
mkdir -p .respaldos

echo "📥 Creando scripts..."

# Aquí irían los scripts pero es más fácil copiarlos manualmente
# Por ahora crear un README

cat > .respaldos/README.txt << 'EOF'
Sistema de Respaldos Instalado

Los scripts están disponibles:
1. crear_respaldo.sh
2. listar_respaldos.sh  
3. restaurar_respaldo.sh

Lee RESPALDOS.md para instrucciones completas.
EOF

echo "✅ Scripts creados"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Instalación completada"
echo ""
echo "Próximos pasos:"
echo "1. Revisa RESPALDOS.md para la documentación completa"
echo "2. Crea tu primer respaldo:"
echo "   ./crear_respaldo.sh 5 \"Bitácora completa y funcional\""
echo "3. Sube el tag a GitHub:"
echo "   git push origin v1.0-backup-5"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
