#!/usr/bin/python3
import os
import urllib.parse

print("Cache-Control: no-cache")
print("Content-type: text/html\n")

print("<!DOCTYPE html><html><head><title>GET Request Echo</title></head>")
print("<body><h1 align='center'>Get Request Echo</h1><hr>")

query_string = os.environ.get('QUERY_STRING', '')
print(f"<b>Query String:</b> {query_string}<br />")

params = urllib.parse.parse_qs(query_string)

for key, values in params.items():
    print(f"{key} = {values[0]}<br />")

print("</body></html>")