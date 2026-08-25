#!/bin/bash
set -e

# Render assegna la porta pubblica tramite la variabile d'ambiente PORT
# (di solito 10000). In locale, senza questa variabile, si usa 80.
PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
