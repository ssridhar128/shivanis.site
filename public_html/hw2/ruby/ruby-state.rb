#!/usr/bin/ruby
require 'cgi'
require 'json'
require 'uri'

cgi = CGI.new
cookie_name = "saved_ruby_name"

# Define the absolute path to your shared database
DB_FILE = "/var/www/shivanis.site/public_html/hw2/fp_database.txt"

# --- Helper Methods ---
def get_fp_db
  return {} unless File.exist?(DB_FILE)
  begin
    db = {}
    File.readlines(DB_FILE).each do |line|
      next unless line.include?('|')
      parts = line.strip.split('|')
      db[parts[0]] = parts[1] if parts.length == 2
    end
    db
  rescue
    {}
  end
end

def save_to_db(vid, name)
  begin
    File.open(DB_FILE, "a") do |f|
      f.puts "#{vid}|#{name}"
      f.flush
    end
  rescue
    # Error handling for permissions
  end
end

# --- 1. AJAX Reassociation Lookup ---
if cgi.has_key?('reassociate_id')
  vid = cgi['reassociate_id']
  db = get_fp_db
  puts cgi.header("type" => "application/json", "charset" => "UTF-8")
  if db.has_key?(vid)
    puts JSON.generate({reassociated: true, name: db[vid]})
  else
    puts JSON.generate({reassociated: false})
  end
  exit
end

# --- 2. Handle Restore Logic ---
if cgi.has_key?('restore_name')
  restore_name = cgi['restore_name']
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => restore_name, 'path' => '/')
  puts cgi.header("type" => "text/html", "cookie" => cookie, "cache-control" => "no-cache")
  # (HTML output matches the Python version below)
end

# --- 3. Handle Regular Logic (Cookies & Form) ---
existing_cookie = cgi.cookies[cookie_name]
saved_name = (existing_cookie && !existing_cookie.empty?) ? CGI.unescape(existing_cookie[0]) : "None"

new_name = cgi['username']
visitor_id = cgi['visitorId']
clear = cgi['clear']

if clear == "true"
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => '', 'expires' => Time.at(0), 'path' => '/')
  puts cgi.header("status" => "REDIRECT", "location" => "ruby-state.rb?just_cleared=true", "cookie" => cookie)
  exit
elsif !new_name.to_s.empty?
  save_to_db(visitor_id, new_name) unless visitor_id.to_s.empty?
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => new_name, 'path' => '/')
  puts cgi.header("status" => "REDIRECT", "location" => "ruby-state.rb", "cookie" => cookie)
  exit
end

# --- 4. HTML Output ---
restored = cgi['restored'] == '1'
puts cgi.header("type" => "text/html", "cache-control" => "no-cache")
puts <<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Ruby State + Fingerprinting</title>
    <script>
        // LoadScript and initFP logic (Identical to your Python script)
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        
        async function initFP() {
            const cdnSources = [
                'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://openfpcdn.io/fingerprintjs/v4/iife.min.js'
            ];
            
            let loaded = false;
            for (const src of cdnSources) {
                try {
                    await loadScript(src);
                    if (typeof FingerprintJS !== 'undefined') { loaded = true; break; }
                } catch (e) {}
            }
            
            if (loaded) {
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                const vid = result.visitorId;
                document.getElementById('visitorIdField').value = vid;
                
                const urlParams = new URLSearchParams(window.location.search);
                if ("#{saved_name}" == "None" && urlParams.get('just_cleared') === 'true') {
                    fetch('ruby-state.rb?reassociate_id=' + vid)
                        .then(res => res.json())
                        .then(data => {
                            if (data.reassociated) {
                                document.getElementById('fp-msg').innerText = "Restoring from fingerprint...";
                                window.location.href = 'ruby-state.rb?restore_name=' + encodeURIComponent(data.name);
                            }
                        });
                }
            }
        }
        document.addEventListener('DOMContentLoaded', initFP);
    </script>
</head>
<body>
    <h1>Ruby State + Fingerprinting</h1>
    <p>Stored Name: <b>#{saved_name}</b></p>
    <form method="POST">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Cookie</button>
    </form>
    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Cookie</button>
    </form>
    <p id="fp-msg" style="color: green; font-weight: bold;">#{restored ? "Restored from fingerprint!" : ""}</p>
</body>
</html>
HTML