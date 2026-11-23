#!/usr/bin/env bash
set -e

CONF_PATH="/home/abdelrahman/me/next-pixel/nginx/next-pixel.test.conf"
HOST_ENTRY="127.0.0.1 next-pixel.test"

echo "=== Configuring next-pixel.test ==="

# 1. Add next-pixel.test to /etc/hosts if not present
if ! grep -q "next-pixel.test" /etc/hosts; then
    echo "Adding '$HOST_ENTRY' to /etc/hosts..."
    echo "$HOST_ENTRY" | sudo tee -a /etc/hosts > /dev/null
else
    echo "next-pixel.test is already present in /etc/hosts."
fi

# 2. Link Nginx virtual host configuration
echo "Linking Nginx configuration..."
sudo ln -sf "$CONF_PATH" /etc/nginx/sites-available/next-pixel.test.conf
sudo ln -sf "$CONF_PATH" /etc/nginx/sites-enabled/next-pixel.test.conf

# 3. Validate Nginx configuration syntax
echo "Testing Nginx configuration syntax..."
sudo nginx -t

# 4. Reload Nginx
echo "Reloading Nginx service..."
sudo systemctl reload nginx

echo "=== Setup complete! ==="
echo "Application URL: http://next-pixel.test"
