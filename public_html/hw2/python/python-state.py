#!/usr/bin/python3
import os
import sys
import urllib.parse
from http import cookies

# 1. Parse existing cookies (UNQUOTE THE VALUE HERE)
cookie = cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
raw_val = cookie.get("saved_name").value if cookie.get("saved_name") else "None"
saved_name = urllib.parse.unquote(raw_val) # Decodes %20 back into a space

# 2. Handle Form Submission
content_length = int(os.environ.get('CONTENT_LENGTH', 0))
post_data = sys.stdin.read(content_length)
params = urllib.parse.parse_qs(post_data)

new_name = params.get('username', [None])[0]
clear = params.get('clear', [None])[0]

# 3. Prepare Headers (QUOTE THE VALUE HERE)
print("Cache-Control: no-cache")
if clear:
    print("Set-Cookie: saved_name=; expires=Thu, 01 Jan 1970 00:00:00 GMT")
    print("Location: python-state.py")
    print("\n")
    sys.exit()
elif new_name:
    # Encodes spaces as %20 so the browser accepts the cookie
    safe_name = urllib.parse.quote(new_name) 
    print(f"Set-Cookie: saved_name={safe_name}")
    print("Location: python-state.py")
    print("\n")
    sys.exit()

print("Content-type: text/html\n")

# 4. Output HTML
print(f"""
<!DOCTYPE html>
<html><head><title>Python State</title></head>
<body>
    <h1>Python Cookie-Based State</h1>
    <p>Stored Name: <b>{saved_name}</b></p>
    <form method="POST">
        <input type="text" name="username">
        <button type="submit">Save to Cookie</button>
    </form>
    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Cookie</button>
    </form>
</body></html>
""")