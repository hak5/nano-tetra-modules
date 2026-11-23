#!/bin/bash
# PineappleTAK (WiFi Pineapple Tetra) - installs, updates or removes the PineappleTAK service.
# Run by the module page (api/module.php) in the background; output goes to
# /tmp/pineappletak-install.log.
#   install.sh install            install or update; settings and data are kept
#   install.sh uninstall [keep]   remove the service ("keep" keeps settings, certificates and loot)
MOD="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ASSETS="$MOD/pineappletak"            # the service, shipped with the module
HOME_DIR=${PTAK_HOME:-/root/pineappletak}   # where the service runs
CONF=${PTAK_CONF:-/etc/pineappletak}
INIT=${PTAK_INIT:-/etc/init.d/pineappletak}
KEEP="core/certs core/geofences.txt core/items.txt core/overlays core/tiles core/watch core/web/auth core/web/tls"

missing() {
    local m=""
    command -v bash >/dev/null 2>&1 || m="$m bash"
    python3 -c 'import sqlite3' >/dev/null 2>&1 || m="$m python3-light python3-sqlite3"
    python3 -c 'import ssl' >/dev/null 2>&1 || m="$m python3-openssl"
    [ -f /etc/ssl/certs/ca-certificates.crt ] || m="$m ca-bundle"
    echo "$m"
}

running() { kill -0 "$(cat /var/run/pineappletak.pid 2>/dev/null)" 2>/dev/null; }

write_init() {
    cat > "$INIT" <<'INIT_EOF'
#!/bin/sh /etc/rc.common
# PineappleTAK - puts the WiFi Pineapple Tetra on the TAK map (installed by the PineappleTAK module)
START=99
STOP=10
USE_PROCD=1

start_service() {
    procd_open_instance
    procd_set_param command /bin/bash /root/pineappletak/pineappletak.sh
    procd_set_param respawn 3600 15 5
    procd_set_param term_timeout 15
    procd_set_param stdout 0
    procd_set_param stderr 0
    procd_close_instance
}
INIT_EOF
    chmod 755 "$INIT"
}

do_install() {
    local need was=0 rel keep=/tmp/pineappletak-keep
    [ -d "$ASSETS" ] || { echo "ERROR: the module is missing its service files - reinstall the module"; return 1; }
    need=$(missing)
    if [ -n "$need" ]; then
        echo "Installing:$need"
        opkg update
        # shellcheck disable=SC2086
        opkg install $need
        need=$(missing)
        [ -z "$need" ] || { echo "ERROR: could not install$need (internet needed)"; return 1; }
    fi
    if running; then was=1; "$INIT" stop; sleep 2; fi
    mkdir -p "$HOME_DIR"
    rm -rf "$keep"
    for rel in $KEEP; do                                  # move your data aside...
        [ -e "$HOME_DIR/$rel" ] || continue
        mkdir -p "$keep/$(dirname "$rel")"; mv "$HOME_DIR/$rel" "$keep/$rel"
    done
    rm -rf "${HOME_DIR:?}"/*                              # ...replace the code...
    cp -a "$ASSETS"/. "$HOME_DIR"/
    for rel in $KEEP; do                                  # ...and put your data back
        [ -e "$keep/$rel" ] || continue
        rm -rf "${HOME_DIR:?}/$rel"; mkdir -p "$HOME_DIR/$(dirname "$rel")"; mv "$keep/$rel" "$HOME_DIR/$rel"
    done
    rm -rf "$keep"
    find "$HOME_DIR" -name '*.sh' -exec chmod 755 {} +
    chmod 755 "$HOME_DIR"/tetra/bin/*
    mkdir -p "$HOME_DIR/core/certs" "$CONF"; chmod 700 "$HOME_DIR/core/certs"
    write_init
    [ "$was" = 1 ] && "$INIT" start
    echo "DONE: PineappleTAK $(sed -n 's/^PTAK_VERSION="\(.*\)"/\1/p' "$HOME_DIR/pineappletak.sh") installed"
    [ -s "$HOME_DIR/core/web/auth" ] || echo "Next: set a console password, then press Start."
}

do_uninstall() {
    if [ -f "$INIT" ]; then "$INIT" stop; "$INIT" disable; rm -f "$INIT"; fi
    if [ "$1" = keep ]; then
        rm -rf "$HOME_DIR/pineappletak.sh" "$HOME_DIR/tetra"
        echo "DONE: PineappleTAK removed; settings, certificates and loot kept for a reinstall"
    else
        rm -rf "$HOME_DIR" "$CONF"
        echo "DONE: PineappleTAK removed with its settings and certificates (loot kept in /root/loot/PineappleTAK)"
    fi
}

case "$1" in
    install)   do_install ;;
    uninstall) do_uninstall "$2" ;;
    *) echo "usage: install.sh install | uninstall [keep]"; exit 1 ;;
esac
