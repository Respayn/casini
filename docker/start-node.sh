#!/bin/sh

# Установка зависимостей, если нет node_modules
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

echo "Starting server..."
npm run dev