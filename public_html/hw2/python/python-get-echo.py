#!/usr/bin/python3
import os
import urllib.parse

print("Cache-Control: no-cache")
print("Content-type: text/html\n")

print("<!DOCTYPE html><html><head><title>GET Request Echo</title></head>")
print("<body><h1 align='center'>Get Request Echo</h1><hr>")

# Get the raw string from environment
query_string = os.environ.get('QUERY_STRING', '')
print(f"<b>Query String:</b> {query_string}<br />")

# Parse the string into a dictionary
params = urllib.parse.parse_qs(query_string)

for key, values in params.items():
    # parse_qs returns a list for each key, so we take the first item
    print(f"{key} = {values[0]}<br />")

print("</body></html>")