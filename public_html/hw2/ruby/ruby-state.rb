#!/usr/bin/ruby
require 'cgi'
require 'json'

cgi = CGI.new
cookie_name = "saved_ruby_name"

# Database file path (one level up from ruby/ directory)
script_dir = File.dirname(File.realdirpath(__FILE__))
db_file = File.join(script_dir, "..", "fp_database.txt")

# Read fingerprint database
def read_fp_db(path)
  return {} unless File.exist?(path)
  db = {}
  File.readlines(path).each do |line|
    parts = line.strip.split("|")
    db[parts[0]] = parts[1] if parts.length == 2
  end
  db
rescue
  {}
end

# Save to fingerprint database
def write_to_db(path, vid, name)
  File.open(path, "a") { |f| f.puts "#{vid}|#{name}" }
rescue
  # ignore errors
end

# Get query string parameters
query_string = ENV['QUERY_STRING'] || ''
query_params = CGI.parse(query_string)

# Handle reassociation lookup (returns JSON)
if query_params.key?('reassociate_id') && !query_params['reassociate_id'].first.to_s.empty?
  vid = query_params['reassociate_id'].first
  db = read_fp_db(db_file)
  print "Content-type: application/json\r\n\r\n"
  if db.key?(vid)
    print JSON.generate({ "reassociated" => true, "name" => db[vid] })
  else
    print JSON.generate({ "reassociated" => false })
  end
  exit
end

# Handle restore cookie from fingerprint
if query_params.key?('restore_name') && !query_params['restore_name'].first.to_s.empty?
  restore_name = CGI.unescape(query_params['restore_name'].first)
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => restore_name, 'path' => '/')
  
  print "Cache-Control: no-cache\r\n"
  print "Set-Cookie: #{cookie}\r\n"
  print "Content-type: text/html\r\n\r\n"
  
  escaped_name = CGI.escapeHTML(restore_name)
  print <<-HTML
<!DOCTYPE html>
<html>
<head><title>Ruby State + Fingerprinting</title></head>
<body>
    <h1>Ruby State + Fingerprinting</h1>
    <p>Stored Name: <b>#{escaped_name}</b></p>
    
    <form method="POST" action="ruby-state.rb">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Cookie</button>
    </form>

    <form method="POST" action="ruby-state.rb" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Cookie</button>
    </form>
    
    <p style="color: green; font-weight: bold;">Restored from fingerprint!</p>
    
    <script>
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
                'https://unpkg.com/@fingerprintjs/fingerprintjs@4/dist/fp.min.js'
            ];
            
            for (const src of cdnSources) {
                try {
                    await loadScript(src);
                    if (typeof FingerprintJS !== 'undefined') break;
                } catch (e) {}
            }
            
            if (typeof FingerprintJS !== 'undefined') {
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                document.getElementById('visitorIdField').value = result.visitorId;
            }
        }
        
        document.addEventListener('DOMContentLoaded', initFP);
    </script>
</body>
</html>
HTML
  exit
end

# Get existing cookie
existing_cookie = cgi.cookies[cookie_name]
saved_name = (existing_cookie && !existing_cookie.empty?) ? existing_cookie[0] : "None"

# Get POST parameters
new_name = cgi['username']
clear = cgi['clear']
visitor_id = cgi['visitorId']

# Handle clear
if clear == "true"
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => '', 'expires' => Time.at(0), 'path' => '/')
  print "Cache-Control: no-cache\r\n"
  print "Set-Cookie: #{cookie}\r\n"
  print "Status: 302 Found\r\n"
  print "Location: ruby-state.rb?just_cleared=true\r\n\r\n"
  exit
end

# Handle save
if new_name && !new_name.to_s.empty?
  # Save to fingerprint database
  if visitor_id && !visitor_id.to_s.empty?
    write_to_db(db_file, visitor_id, new_name)
  end
  
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => new_name, 'path' => '/')
  print "Cache-Control: no-cache\r\n"
  print "Set-Cookie: #{cookie}\r\n"
  print "Status: 302 Found\r\n"
  print "Location: ruby-state.rb\r\n\r\n"
  exit
end

# Check if just cleared
just_cleared = query_params['just_cleared']&.first == 'true'

escaped_name = CGI.escapeHTML(saved_name)

print "Cache-Control: no-cache\r\n"
print "Content-type: text/html\r\n\r\n"

print <<-HTML
<!DOCTYPE html>
<html>
<head>
    <title>Ruby State + Fingerprinting</title>
    <script>
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
                'https://unpkg.com/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://openfpcdn.io/fingerprintjs/v4/iife.min.js'
            ];
            
            let loaded = false;
            for (const src of cdnSources) {
                try {
                    await loadScript(src);
                    if (typeof FingerprintJS !== 'undefined') {
                        loaded = true;
                        break;
                    }
                } catch (e) {
                    console.log("Failed to load from:", src);
                }
            }
            
            if (!loaded || typeof FingerprintJS === 'undefined') {
                console.error("Could not load FingerprintJS");
                return;
            }
            
            try {
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                const vid = result.visitorId;

                console.log("1. Fingerprint loaded, visitorId:", vid);
                document.getElementById('visitorIdField').value = vid;
                
                const urlParams = new URLSearchParams(window.location.search);
                const justCleared = urlParams.get('just_cleared') === 'true';
                const savedName = "#{escaped_name}";
                
                console.log("2. justCleared:", justCleared, "savedName:", savedName);

                if ((savedName == "None" || savedName == "") && justCleared) {
                    console.log("3. Conditions met, fetching reassociate_id...");
                    fetch('ruby-state.rb?reassociate_id=' + vid)
                        .then(res => res.json())
                        .then(data => {
                            console.log("4. Server response:", data);
                            if (data.reassociated) {
                                console.log("5. Reassociated! Redirecting to restore_name...");
                                document.getElementById('fp-msg').innerText = "Restoring from fingerprint...";
                                window.location.href = 'ruby-state.rb?restore_name=' + encodeURIComponent(data.name);
                            } else {
                                console.log("5. NOT reassociated - fingerprint not found in database");
                                document.getElementById('fp-msg').innerText = "Fingerprint not found in database";
                                document.getElementById('fp-msg').style.color = "red";
                            }
                        })
                        .catch(err => {
                            console.error("4. Fetch error:", err);
                        });
                } else {
                    console.log("3. Conditions NOT met for reassociation");
                }
            } catch (e) {
                console.error("FingerprintJS error:", e);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const vid = document.getElementById('visitorIdField').value;
                if (!vid) {
                    alert('Please wait for fingerprint to load before submitting');
                    e.preventDefault();
                }
            });
            
            initFP();
        });
    </script>
</head>
<body>
    <h1>Ruby State + Fingerprinting</h1>
    <p>Stored Name: <b>#{escaped_name}</b></p>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Cookie</button>
    </form>

    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Cookie</button>
    </form>
    
    <p id="fp-msg" style="color: green; font-weight: bold;"></p>
</body>
</html>
HTML