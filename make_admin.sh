#!/bin/bash

# ==============================================================================
# ircPlanet Services Admin Granter
# Usage: ./make_admin.sh <nickname>
# ==============================================================================

# --- Configuration ---
# Update these to match your cs.ini / ns.ini settings
DB_HOST="localhost"
DB_USER="ircd"
DB_PASS=""           # Leave empty if no password, or set your password here
DB_NAME="ircplanet_services"

# Path to your ircplanet directory (current directory by default)
SERVICES_ROOT="$HOME/ircplanet"
# ---------------------

# 1. Input Validation
USERNAME="$1"

if [ -z "$USERNAME" ]; then
    echo "Usage: $0 <nickname>"
    echo "Example: $0 Speakz"
    exit 1
fi

# Function to run MySQL queries
run_query() {
    local query="$1"
    if [ -n "$DB_PASS" ]; then
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -se "$query"
    else
        mysql -u"$DB_USER" -h"$DB_HOST" "$DB_NAME" -se "$query"
    fi
}

# 2. Get Account ID
echo "[-] Looking up account ID for '$USERNAME'..."
ACCOUNT_ID=$(run_query "SELECT account_id FROM accounts WHERE name = '$USERNAME';")

if [ -z "$ACCOUNT_ID" ]; then
    echo "[!] Error: User '$USERNAME' not found in the 'accounts' table."
    echo "    Please register first using: /msg N register <pass> <email>"
    exit 1
fi

echo "[+] Found Account ID: $ACCOUNT_ID"

# 3. Grant Admin Access (Level 1000)
ADMIN_TABLES="os_admins cs_admins ns_admins ds_admins ss_admins"
LEVEL=1000

echo "[-] Granting Level $LEVEL access..."

for TABLE in $ADMIN_TABLES; do
    # Uses ON DUPLICATE KEY UPDATE to handle cases where you might already be an admin
    QUERY="INSERT INTO $TABLE (user_id, level) VALUES ($ACCOUNT_ID, $LEVEL) ON DUPLICATE KEY UPDATE level=$LEVEL;"
    run_query "$QUERY"
    if [ $? -eq 0 ]; then
        echo "    -> Updated $TABLE"
    else
        echo "    [!] Failed to update $TABLE"
    fi
done

# 4. Restart Services
echo "[-] Restarting services to apply changes..."

# Kill existing service processes (matches "php os.php", "php cs.php", etc.)
pkill -f "php [oncds]s.php"
sleep 2

# Helper function to start a service
start_service() {
    local dir="$1"
    local script="$2"
    if [ -d "$SERVICES_ROOT/$dir" ]; then
        echo "    -> Starting $dir Service..."
        cd "$SERVICES_ROOT/$dir" && ./$script
    else
        echo "    [!] Directory $SERVICES_ROOT/$dir not found."
    fi
}

start_service "Operator" "os"
start_service "Channel" "cs"
start_service "Nickname" "ns"
start_service "Defense" "ds"
start_service "Stat" "ss"

echo ""
echo "[*] Success! $USERNAME now has Level 1000 access."
echo "    Verify by typing: /msg O showcommands"
