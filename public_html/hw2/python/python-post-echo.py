#!/usr/bin/python3
import sys
import os
import urllib.parse

print("Cache-Control: no-cache")
print("Content-type: text/html\n")

print("<!DOCTYPE html><html><head><title>POST Request Echo</title></head>")
print("<body><h1 align='center'>POST Request Echo</h1><hr>")

# 1. Read the exact number of bytes specified in CONTENT_LENGTH
content_length = int(os.environ.get('CONTENT_LENGTH', 0))
post_data = sys.stdin.read(content_length)

print(f"<b>Message Body:</b> {post_data}<br />")
print("<ul>")

# 2. Parse the URL-encoded data
params = urllib.parse.parse_qs(post_data)
for key, values in params.items():
    print(f"<li>{key} = {values[0]}</li>")

print("</ul></body></html>")