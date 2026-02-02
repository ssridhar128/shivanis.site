#!/usr/bin/python3
import os
import sys
import urllib.parse
from http import cookies

# Get the directory where this script is located using realpath for symlinks
SCRIPT_DIR = os.path.dirname(os.path.realpath(__file__))
# Database file is one level up from the python/ directory
DB_FILE = os.path.normpath(os.path.join(SCRIPT_DIR, "..", "fp_database.txt"))

# Debug info stored globally
debug_info = []

def get_fp_db():
    if not os.path.exists(DB_FILE): 
        debug_info.append(f"DB file does not exist: {DB_FILE}")
        return {}
    try:
        with open(DB_FILE, "r") as f:
            data = dict(line.strip().split("|") for line in f if "|" in line)
            debug_info.append(f"Loaded {len(data)} entries from DB")
            return data
    except Exception as e:
        debug_info.append(f"Error reading DB: {str(e)}")
        return {}

def save_to_db(vid, name):
    try:
        debug_info.append(f"Attempting to save: vid={vid}, name={name}, path={DB_FILE}")
        with open(DB_FILE, "a") as f:
            f.write(f"{vid}|{name}\n")
            f.flush()
            os.fsync(f.fileno())
        debug_info.append("Save successful!")
        return True
    except Exception as e:
        debug_info.append(f"DATABASE ERROR: {str(e)}")
        return False

query = urllib.parse.parse_qs(os.environ.get('QUERY_STRING', ''))
if 'reassociate_id' in query:
    vid = query['reassociate_id'][0]
    db = get_fp_db()
    import json
    print("Content-type: application/json\n")
    if vid in db:
        print(json.dumps({"reassociated": True, "name": db[vid]}))
    else:
        print(json.dumps({"reassociated": False}))
    sys.exit()


cookie = cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
raw_val = cookie.get("saved_name").value if cookie.get("saved_name") else "None"
saved_name = urllib.parse.unquote(raw_val)

content_length = int(os.environ.get('CONTENT_LENGTH', 0))
post_data = sys.stdin.read(content_length)
params = urllib.parse.parse_qs(post_data)

new_name = params.get('username', [None])[0]
clear = params.get('clear', [None])[0]

print("Cache-Control: no-cache")
if clear:
    print("Set-Cookie: saved_name=; expires=Thu, 01 Jan 1970 00:00:00 GMT")
    # Add ?just_cleared=true to the redirect URL
    print("Location: python-state.py?just_cleared=true")
    print("\n")
    sys.exit()
elif new_name:
    visitor_id = params.get('visitorId', [None])[0]
    save_result = False
    if visitor_id and visitor_id.strip():
        save_result = save_to_db(visitor_id, new_name)
    else:
        debug_info.append(f"visitorId missing or empty: '{visitor_id}'")
    
    safe_name = urllib.parse.quote(new_name) 
    print(f"Set-Cookie: saved_name={safe_name}")
    # Pass debug info via query param for visibility
    debug_param = "&save_ok=1" if save_result else "&save_ok=0"
    print(f"Location: python-state.py?fp_saved={1 if visitor_id else 0}{debug_param}")
    print("\n") # Required blank line after headers
    sys.exit()

# Get query params for status display
query_params = urllib.parse.parse_qs(os.environ.get('QUERY_STRING', ''))
fp_saved = query_params.get('fp_saved', [''])[0]
save_ok = query_params.get('save_ok', [''])[0]

# Check DB status
db_exists = os.path.exists(DB_FILE)
db_writable = os.access(os.path.dirname(DB_FILE), os.W_OK) if os.path.exists(os.path.dirname(DB_FILE)) else False

print("Content-type: text/html\n")

print(f"""
<!DOCTYPE html>
<html>
<head>
    <title>Python State + Fingerprinting</title>
    <script>
        // Show protocol for debugging
        document.addEventListener('DOMContentLoaded', function() {{
            document.getElementById('protocol-info').innerText = window.location.protocol;
        }});
        
        // Try loading FingerprintJS from multiple sources
        function loadScript(src) {{
            return new Promise((resolve, reject) => {{
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            }});
        }}
        
        async function initFP() {{
            const vidDisplay = document.getElementById('vid-display');
            vidDisplay.innerText = "Loading library...";
            
            // List of CDN sources to try
            const cdnSources = [
                'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://unpkg.com/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://openfpcdn.io/fingerprintjs/v4/iife.min.js'
            ];
            
            let loaded = false;
            for (const src of cdnSources) {{
                try {{
                    vidDisplay.innerText = "Trying: " + src.split('/')[2] + "...";
                    await loadScript(src);
                    if (typeof FingerprintJS !== 'undefined') {{
                        loaded = true;
                        break;
                    }}
                }} catch (e) {{
                    console.log("Failed to load from:", src);
                }}
            }}
            
            if (!loaded || typeof FingerprintJS === 'undefined') {{
                vidDisplay.innerText = "ERROR: Could not load FingerprintJS from any CDN. Check HTTPS & CSP.";
                vidDisplay.style.color = 'red';
                return;
            }}
            
            try {{
                vidDisplay.innerText = "Getting fingerprint...";
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                const vid = result.visitorId;

                document.getElementById('visitorIdField').value = vid;
                vidDisplay.innerText = vid;
                vidDisplay.style.color = 'green';
                
                const urlParams = new URLSearchParams(window.location.search);
                const justCleared = urlParams.get('just_cleared') === 'true';

                if (("{saved_name}" == "None" || "{saved_name}" == "") && justCleared) {{
                    fetch('python-state.py?reassociate_id=' + vid)
                        .then(res => res.json())
                        .then(data => {{
                            if (data.reassociated) {{
                                document.getElementById('fp-msg').innerText = "Reassociated via Fingerprint: " + data.name;
                            }}
                        }});
                }}
            }} catch (e) {{
                vidDisplay.innerText = "ERROR: " + e.message;
                vidDisplay.style.color = 'red';
                console.error("FingerprintJS error:", e);
            }}
        }}
        
        // Prevent form submission if visitorId is empty
        document.addEventListener('DOMContentLoaded', function() {{
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {{
                const vid = document.getElementById('visitorIdField').value;
                if (!vid) {{
                    alert('Please wait for fingerprint to load before submitting');
                    e.preventDefault();
                }}
            }});
            
            // Start loading fingerprint
            initFP();
        }});
    </script>
</head>
<body>
    <h1>Python State (attempt with Fingerprinting)</h1>
    <p>Stored Name: <b>{saved_name}</b></p>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Cookie</button>
    </form>

    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Cookie</button>
    </form>
    
    <p id="fp-msg" style="color: blue; font-weight: bold;"></p>
    
    <hr>
    <h3>Debug Info:</h3>
    <ul>
        <li>Protocol: <span id="protocol-info" style="font-weight:bold;">(checking...)</span> <small>(FingerprintJS requires HTTPS)</small></li>
        <li>Visitor ID: <span id="vid-display" style="color:green;">(loading...)</span></li>
        <li>DB Path: <code>{DB_FILE}</code></li>
        <li>DB Exists: {db_exists}</li>
        <li>Directory Writable: {db_writable}</li>
        <li>Last FP Saved: {fp_saved if fp_saved else 'N/A'} (1=yes, 0=no visitorId)</li>
        <li>Last Save OK: {save_ok if save_ok else 'N/A'} (1=success, 0=failed)</li>
        <li>Script Dir: <code>{SCRIPT_DIR}</code></li>
    </ul>
</body>
</html>
""")