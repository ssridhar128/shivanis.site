#!/usr/bin/python3
import os
import sys
import urllib.parse
from http import cookies

# 1. Parse existing cookies
cookie = cookies.SimpleCookie(os.environ.get("HTTP_COOKIE"))
saved_name = cookie.get("saved_name").value if cookie.get("saved_name") else "None"

# 2. Handle Form Submission
content_length = int(os.environ.get('CONTENT_LENGTH', 0))
post_data = sys.stdin.read(content_length)
params = urllib.parse.parse_qs(post_data)

new_name = params.get('username', [None])[0]
clear = params.get('clear', [None])[0]

# 3. Prepare Headers
print("Cache-Control: no-cache")
if clear:
    print("Set-Cookie: saved_name=; expires=Thu, 01 Jan 1970 00:00:00 GMT")
    saved_name = "None"
elif new_name:
    print(f"Set-Cookie: saved_name={new_name}")
    saved_name = new_name

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