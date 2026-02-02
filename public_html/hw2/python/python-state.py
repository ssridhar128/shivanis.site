#!/usr/bin/python3
import os
import sys
import urllib.parse
from http import cookies

DB_FILE = "/var/www/shivanis.site/public_html/hw2/fp_database.txt"

def get_fp_db():
    if not os.path.exists(DB_FILE): return {}
    with open(DB_FILE, "r") as f:
        return dict(line.strip().split("|") for line in f if "|" in line)

def save_to_db(vid, name):
    try:
        with open(DB_FILE, "a") as f:
            f.write(f"{vid}|{name}\n")
            f.flush()
            os.fsync(f.fileno())
    except Exception as e:
        sys.stderr.write(f"DATABASE ERROR: {str(e)}\n")

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
    if visitor_id:
        save_to_db(visitor_id, new_name)
    else:
        sys.stderr.write("DEBUG: visitorId was missing from POST data\n")

print("Content-type: text/html\n")

print(f"""
<!DOCTYPE html>
<html>
<head>
    <title>Python State + Fingerprinting</title>
    <script>
        const fpPromise = import('https://openfpcdn.io/fingerprintjs/v4')
            .then(FingerprintJS => FingerprintJS.load());

        async function initFP() {{
            const fp = await fpPromise;
            const result = await fp.get();
            const vid = result.visitorId;

            document.getElementById('visitorIdField').value = vid;
            const urlParams = new URLSearchParams(window.location.search);
            const justCleared = urlParams.get('just_cleared') === 'true';

            if ("{saved_name}" == "None" || "{saved_name}" == "" && justCleared) {{
                fetch('python-state.py?reassociate_id=' + vid)
                    .then(res => res.json())
                    .then(data => {{
                        if (data.reassociated) {{
                            document.getElementById('fp-msg').innerText = "Reassociated via Fingerprint: " + data.name;
                        }}
                    }});
            }}
        }}
        window.onload = initFP;
    </script>
</head>
<body>
    <h1>Python State (with Fingerprinting)</h1>
    <p>Stored Name: <b>{saved_name}</b></p>
    
    <form method="POST">
        <input type="text" name="username">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Cookie</button>
    </form>

    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Cookie</button>
    </form>
    
    <p id="fp-msg" style="color: blue; font-weight: bold;"></p>
</body>
</html>
""")