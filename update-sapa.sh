#!/bin/bash

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # Sin color

# Verificar que se pasó un mensaje de commit
if [ -z "$1" ]; then
    echo -e "${RED}❌ Error: Debes proporcionar un mensaje de commit${NC}"
    echo "Uso: bash update-sapa.sh 'tu mensaje aquí'"
    exit 1
fi

COMMIT_MSG="$1"

echo -e "${BLUE}📦 Agregando cambios...${NC}"
git add .

echo -e "${BLUE}💾 Haciendo commit...${NC}"
git commit -m "$COMMIT_MSG"

echo -e "${BLUE}🚀 Subiendo a GitHub...${NC}"
git push origin main

echo -e "${GREEN}✅ Cambios subidos a GitHub${NC}"
echo -e "${BLUE}🔄 Ahora actualiza el servidor ejecutando:${NC}"
echo "ssh root@62.171.134.111 'cd /root/sapa-v2 && git pull origin main'"