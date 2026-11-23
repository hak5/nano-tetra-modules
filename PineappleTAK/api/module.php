<?php namespace pineapple;

/*
 * PineappleTAK module for the WiFi Pineapple Tetra - back end.
 *
 * Installs and controls the PineappleTAK service (/root/pineappletak,
 * /etc/init.d/pineappletak). Everything TAK-related is done in PineappleTAK's
 * own web console, which this page opens.
 */
class PineappleTAK extends Module
{
    const MODULE_DIR = '/pineapple/modules/PineappleTAK';
    const HOME = '/root/pineappletak';
    const CONF = '/etc/pineappletak';
    const INIT = '/etc/init.d/pineappletak';
    const RUN = '/tmp/pineappletak';
    const LOG = '/tmp/pineappletak.log';
    const INSTALL_LOG = '/tmp/pineappletak-install.log';
    const INSTALL_PID = '/tmp/pineappletak-install.pid';

    public function route()
    {
        switch ($this->request->action) {
            case 'status':
                $this->status();
                break;
            case 'install':
                $this->install();
                break;
            case 'setPassword':
                $this->setPassword();
                break;
            case 'service':
                $this->service();
                break;
            case 'uninstall':
                $this->uninstall();
                break;
            case 'setListen':
                $this->setListen();
                break;
        }
    }

    private function firstLine($path)
    {
        if (!is_file($path)) {
            return '';
        }
        $fh = @fopen($path, 'r');
        if (!$fh) {
            return '';
        }
        $line = fgets($fh);
        fclose($fh);
        return $line === false ? '' : trim($line);
    }

    private function conf($key, $default = '')
    {
        $v = $this->firstLine(self::CONF . '/' . $key);
        return $v !== '' ? $v : $default;
    }

    private function tail($path, $n)
    {
        if (!is_file($path)) {
            return array();
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        return $lines === false ? array() : array_slice($lines, -$n);
    }

    private function versionOf($base)
    {
        foreach ($this->tail($base . '/pineappletak.sh', 400) as $line) {
            if (strpos($line, 'PTAK_VERSION=') === 0) {
                return trim(substr($line, 13), "\" \t");
            }
        }
        return '';
    }

    private function pidAlive($pidFile)
    {
        $pid = $this->firstLine($pidFile);
        return preg_match('/^[0-9]+$/', $pid) === 1 && file_exists('/proc/' . $pid);      // no ctype on the Tetra
    }

    private function installed()
    {
        return is_file(self::HOME . '/pineappletak.sh') && is_file(self::INIT);
    }

    private function running()
    {
        return $this->pidAlive('/var/run/pineappletak.pid');
    }

    private function enabled()
    {
        return count(glob('/etc/rc.d/S*pineappletak')) > 0;
    }

    private function installing()
    {
        return $this->pidAlive(self::INSTALL_PID);
    }

    private function passwordSet()
    {
        $auth = self::HOME . '/core/web/auth';
        return is_file($auth) && filesize($auth) > 0;
    }

    private function missingPackages()
    {
        $need = array();
        exec('python3 -c "import sqlite3" 2>/dev/null', $out, $rc);
        if ($rc !== 0) {
            $need[] = 'python3-light';
            $need[] = 'python3-sqlite3';
        }
        exec('python3 -c "import ssl" 2>/dev/null', $out, $rc);
        if ($rc !== 0) {
            $need[] = 'python3-openssl';
        }
        if (!is_file('/etc/ssl/certs/ca-certificates.crt')) {
            $need[] = 'ca-bundle';
        }
        return array_values(array_unique($need));
    }

    private function consoleUrls()
    {
        $port = $this->conf('web_port', '8484');
        $scheme = $this->conf('web_https') === '1' ? 'https' : 'http';
        $ips = array();
        $lan = trim(exec('uci -q get network.lan.ipaddr 2>/dev/null'));
        if ($lan !== '') {
            $ips[] = explode('/', $lan)[0];
        }
        $listen = $this->conf('web_listen', 'all');
        if ($listen === 'all') {
            $name = $this->firstLine('/proc/sys/kernel/hostname');
            if ($name !== '' && $name !== 'OpenWrt') {
                $ips[] = $name;
            }
        }
        if ($listen === 'vpn' || $listen === 'all') {      // same choices as the console's "Listen on"
            exec('ip -4 -o addr show', $lines);
            foreach ($lines as $line) {
                $p = preg_split('/\s+/', trim($line));
                if (count($p) > 3 && $p[1] !== 'lo' && ($listen === 'all' || in_array($p[1], array('wt0', 'wg0', 'tun0')))) {
                    $ips[] = explode('/', $p[3])[0];
                }
            }
        }
        if (!$ips) {
            $ips[] = '172.16.42.1';
        }
        $urls = array();
        foreach (array_unique($ips) as $ip) {
            $urls[] = $scheme . '://' . $ip . ':' . $port;
        }
        return $urls;
    }

    private function status()
    {
        $st = explode('|', $this->firstLine(self::RUN . '/status'));
        $gps = preg_split('/\s+/', $this->firstLine(self::RUN . '/gps'));
        if (count($gps) > 7 && $gps[0] === '1') {
            $position = 'GPS fix, ' . $gps[7] . ' satellites';
        } elseif (is_file(self::RUN . '/gps.dev')) {
            $position = 'GPS on ' . $this->firstLine(self::RUN . '/gps.dev') . ', no fix yet';
        } elseif ($this->conf('manual_pos') !== '') {
            $position = 'manual position';
        } else {
            $position = 'no GPS and no manual position';
        }
        $host = $this->conf('host');
        $this->response = array(
            'success' => true,
            'installed' => $this->installed(),
            'installing' => $this->installing(),
            'installLog' => $this->tail(self::INSTALL_LOG, 8),
            'moduleVersion' => $this->versionOf(self::MODULE_DIR . '/pineappletak'),
            'serviceVersion' => $this->versionOf(self::HOME),
            'running' => $this->running(),
            'enabled' => $this->enabled(),
            'passwordSet' => $this->passwordSet(),
            'configured' => $this->conf('configured') === '1',
            'setupMode' => is_file(self::RUN . '/setup_mode'),
            'link' => isset($st[0]) ? $st[0] : '',
            'lastReport' => isset($st[1]) ? $st[1] : '',
            'error' => isset($st[2]) ? $st[2] : '',
            'callsign' => $this->conf('callsign'),
            'server' => $host !== '' ? sprintf('%s:%s (%s)', $host, $this->conf('port', '8089'), $this->conf('proto', 'tls')) : '',
            'position' => $position,
            'urls' => $this->consoleUrls(),
            'listen' => $this->conf('web_listen', 'all'),
            'port' => $this->conf('web_port', '8484'),
            'scheme' => $this->conf('web_https') === '1' ? 'https' : 'http',
            'missing' => $this->missingPackages(),
            'log' => $this->tail(self::LOG, 12),
        );
    }

    private function runInstaller($args)
    {
        if ($this->installing()) {
            $this->response = array('success' => false, 'message' => 'An install is already running');
            return;
        }
        if (!is_file(self::MODULE_DIR . '/install.sh')) {
            $this->response = array('success' => false, 'message' => 'The module is missing install.sh - reinstall the module');
            return;
        }
        // in the background: opkg can take a few minutes; the page follows the log
        exec(sprintf('(echo $$ > %s; exec /bin/bash %s %s) > %s 2>&1 &',
            escapeshellarg(self::INSTALL_PID), escapeshellarg(self::MODULE_DIR . '/install.sh'), $args,
            escapeshellarg(self::INSTALL_LOG)));
        $this->response = array('success' => true, 'message' => 'Working - this can take a few minutes');
    }

    private function install()
    {
        $this->runInstaller('install');
    }

    private function uninstall()
    {
        $this->runInstaller(empty($this->request->keepData) ? 'uninstall' : 'uninstall keep');
    }

    private function setPassword()
    {
        $pw = isset($this->request->password) ? (string) $this->request->password : '';
        if (strlen($pw) < 8) {
            $this->response = array('success' => false, 'message' => 'Use at least 8 characters');
            return;
        }
        if (!$this->installed()) {
            $this->response = array('success' => false, 'message' => 'Install PineappleTAK first');
            return;
        }
        // (a command string: PHP 7.2 on the Tetra has no array form)
        $proc = proc_open(
            'python3 -B ' . escapeshellarg(self::HOME . '/core/web/pagertak_web.py') . ' --hash-password',
            array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
            $pipes
        );
        if (!is_resource($proc)) {
            $this->response = array('success' => false, 'message' => 'Could not set the password');
            return;
        }
        fwrite($pipes[0], $pw . "\n");
        fclose($pipes[0]);
        $hash = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($proc) !== 0 || $hash === '') {
            $this->response = array('success' => false, 'message' => 'Could not set the password');
            return;
        }
        $path = self::HOME . '/core/web/auth';
        $old = umask(077);
        $ok = file_put_contents($path . '.tmp', $hash . "\n") !== false && rename($path . '.tmp', $path);
        umask($old);
        $this->response = array('success' => $ok, 'message' => $ok ? 'Console password set' : 'Could not save the password');
    }

    private function service()
    {
        $op = isset($this->request->op) ? (string) $this->request->op : '';
        $done = array('start' => 'Started', 'stop' => 'Stopped', 'restart' => 'Restarted',
            'enable' => 'Starts when the Pineapple boots', 'disable' => 'No longer starts at boot');
        if (!isset($done[$op])) {
            $this->response = array('success' => false, 'message' => 'Unknown action');
            return;
        }
        if (!$this->installed()) {
            $this->response = array('success' => false, 'message' => 'Install PineappleTAK first');
            return;
        }
        if (($op === 'start' || $op === 'restart') && !$this->passwordSet()) {
            $this->response = array('success' => false, 'message' => 'Set a console password first');
            return;
        }
        exec(self::INIT . ' ' . $op . ' 2>&1');
        if ($op === 'start' || $op === 'restart') {
            sleep(3);
            if (!$this->running()) {
                $this->response = array('success' => false, 'message' => 'PineappleTAK did not start - see Recent activity');
                return;
            }
        }
        $this->response = array('success' => true, 'message' => $done[$op]);
    }

    // mode: all | vpn | usb - where the console can be reached. Restarts PineappleTAK if it is running.
    private function setListen()
    {
        $mode = isset($this->request->mode) ? (string) $this->request->mode : '';
        $text = array('all' => 'Console listens on all interfaces (host name, IP and USB)',
            'vpn' => 'Console listens on USB + VPN only', 'usb' => 'Console listens on the USB address only');
        if (!isset($text[$mode])) {
            $this->response = array('success' => false, 'message' => 'Unknown choice');
            return;
        }
        if (!$this->installed()) {
            $this->response = array('success' => false, 'message' => 'Install PineappleTAK first');
            return;
        }
        if (!is_dir(self::CONF)) {
            mkdir(self::CONF, 0755, true);
        }
        if (file_put_contents(self::CONF . '/web_listen', $mode . "\n") === false) {
            $this->response = array('success' => false, 'message' => 'Could not save the setting');
            return;
        }
        $msg = $text[$mode];
        if ($this->running()) {
            exec(self::INIT . ' restart 2>&1');
            sleep(3);
            $msg .= ' - PineappleTAK restarted';
        }
        $this->response = array('success' => true, 'message' => $msg);
    }
}
