<?php
// OPcache preload — loaded at FPM startup, before any request (including rfc1867).
// Evil::__destruct writes /tmp/destruct_triggered.txt when Chain C fires.
class Evil {
    public $cmd = 'id';
    public function __destruct() {
        if ($this->cmd) {
            $out = @shell_exec($this->cmd);
            @file_put_contents('/tmp/destruct_triggered.txt', $out);
            error_log('[Chain-C] Evil::__destruct fired: ' . trim((string)$out));
        }
    }
}
