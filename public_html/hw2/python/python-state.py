#!/usr/bin/python3
import os
import sys
import urllib.parse
from http import cookies

# Get the directory where this script is located using realpath for symlinks
SCRIPT_DIR = os.path.dirname(os.path.realpath(__file__))
# Database file is one level up from the python/ directory
DB_FILE = os.path.normpath(os.path.join(SCRIPT_DIR, "..", "fp_database.txt"))

def get_fp_db():
    if not os.path.exists(DB_FILE): 
        return {}
    try:
        with open(DB_FILE, "r") as f:
            return dict(line.strip().split("|") for line in f if "|" in line)
    except:
        return {}

def save_to_db(vid, name):
    try:
        with open(DB_FILE, "a") as f:
            f.write(f"{vid}|{name}\n")
            f.flush()
            os.fsync(f.fileno())
        return True
    except:
        return False

query = urllib.parse.parse_qs(os.environ.get('QUERY_STRING', ''))

# Handle reassociation lookup (returns JSON)
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

# Handle restore cookie from fingerprint (sets cookie and redirects)
if 'restore_name' in query:
    restore_name = query['restore_name'][0]
    safe_name = urllib.parse.quote(restore_name)
    print("Cache-Control: no-cache")
    print(f"Set-Cookie: saved_name={safe_name}; Path=/")
    print("Location: python-state.py?restored=1")
    print("\n")
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
    print("Set-Cookie: saved_name=; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/")
    print("Location: python-state.py?just_cleared=true")
    print("\n")
    sys.exit()
elif new_name:
    visitor_id = params.get('visitorId', [None])[0]
    if visitor_id and visitor_id.strip():
        save_to_db(visitor_id, new_name)
    
    safe_name = urllib.parse.quote(new_name) 
    print(f"Set-Cookie: saved_name={safe_name}; Path=/")
    print("Location: python-state.py")
    print("\n")
    sys.exit()

# Check if restored from fingerprint
query_params = urllib.parse.parse_qs(os.environ.get('QUERY_STRING', ''))
restored = query_params.get('restored', [''])[0]

print("Content-type: text/html\n")

print(f"""
<!DOCTYPE html>
<html>
<head>
    <title>Python State + Fingerprinting</title>
    <script>
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
            const cdnSources = [
                'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://unpkg.com/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://openfpcdn.io/fingerprintjs/v4/iife.min.js'
            ];
            
            let loaded = false;
            for (const src of cdnSources) {{
                try {{
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
                console.error("Could not load FingerprintJS");
                return;
            }}
            
            try {{
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                const vid = result.visitorId;

                document.getElementById('visitorIdField').value = vid;
                
                const urlParams = new URLSearchParams(window.location.search);
                const justCleared = urlParams.get('just_cleared') === 'true';

                // If cookie was just cleared, try to reassociate from fingerprint database
                if (("{saved_name}" == "None" || "{saved_name}" == "") && justCleared) {{
                    fetch('python-state.py?reassociate_id=' + vid)
                        .then(res => res.json())
                        .then(data => {{
                            if (data.reassociated) {{
                                document.getElementById('fp-msg').innerText = "Restoring from fingerprint...";
                                window.location.href = 'python-state.py?restore_name=' + encodeURIComponent(data.name);
                            }}
                        }});
                }}
            }} catch (e) {{
                console.error("FingerprintJS error:", e);
            }}
        }}
        
        document.addEventListener('DOMContentLoaded', function() {{
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {{
                const vid = document.getElementById('visitorIdField').value;
                if (!vid) {{
                    alert('Please wait for fingerprint to load before submitting');
                    e.preventDefault();
                }}
            }});
            
            initFP();
        }});
    </script>
</head>
<body>
    <h1>Python State + Fingerprinting</h1>
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
    
    <p id="fp-msg" style="color: green; font-weight: bold;">{"Restored from fingerprint!" if restored == "1" else ""}</p>
</body>
</html>
""")