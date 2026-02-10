#!/bin/bash
set -e  # Detener en caso de error

SERVICE_NAME="php"
COMPOSE_PATH="/root/proyectos/drogueriajorge"
START_TIME=$(date +%s)

# Función para ejecutar comandos en el contenedor
run() {
    docker compose exec -T $SERVICE_NAME "$@"
}

# Función para manejar errores
cleanup() {
    if [ $? -ne 0 ]; then
        echo ""
        echo "❌ Error durante el deploy. Reactivando aplicación..."
        run php artisan up 2>/dev/null || true
    fi
}
trap cleanup EXIT

cd $COMPOSE_PATH

echo "🚀 Iniciando deploy de Droguería Jorge..."
echo "   $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Activar modo mantenimiento
echo "� Activando modo mantenimiento..."
run php artisan down --retry=60 --refresh=5 2>/dev/null || true

# Obtener últimos cambios
echo "� Descargando últimos cambios desde Git..."
cd public
git config core.autocrlf false
git fetch origin main
git reset --hard origin/main
cd ..

# Instalar dependencias (en paralelo si es posible)
echo "� Instalando dependencias..."
run composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Solo instalar npm si package.json cambió
echo "📦 Verificando dependencias de Node.js..."
run npm ci --production=false 2>/dev/null || run npm install --production=false

# Compilar assets
echo "🔨 Compilando assets con Vite..."
run npm run build

# Publicar assets de Livewire (respaldo)
echo "📄 Publicando assets de Livewire..."
run php artisan livewire:publish --assets 2>/dev/null || true

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
run php artisan migrate --force

# Ejecutar seeders pendientes
echo "🌱 Ejecutando seeders pendientes..."
run php artisan db:seed-pending --force

# Optimizar (un solo comando hace todo)
echo "⚡ Optimizando aplicación..."
run php artisan optimize:clear
run php artisan optimize

# Reiniciar queue workers si existen
echo "🔄 Reiniciando workers..."
run php artisan queue:restart 2>/dev/null || true

# Desactivar modo mantenimiento
echo "🔓 Desactivando modo mantenimiento..."
run php artisan up

# Calcular tiempo total
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo "✅ Deploy completado exitosamente!"
echo "⏱️  Tiempo total: ${DURATION}s"
echo "🌐 https://drogueriajorge.com"
