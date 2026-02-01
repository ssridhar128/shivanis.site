#!/usr/bin/python3
import os
import sys

print("Cache-Control: no-cache")
print("Content-type: text/html\n")

print("<!DOCTYPE html><html><head><title>General Request Echo</title></head>")
print("<body><h1 align='center'>General Request Echo</h1><hr>")

# 1. Access Environment Variables
print(f"<p><b>HTTP Protocol:</b> {os.environ.get('SERVER_PROTOCOL', 'N/A')}</p>")
print(f"<p><b>HTTP Method:</b> {os.environ.get('REQUEST_METHOD', 'N/A')}</p>")
print(f"<p><b>Query String:</b> {os.environ.get('QUERY_STRING', 'N/A')}</p>")

# 2. Read from Standard Input (Message Body)
content_length = int(os.environ.get('CONTENT_LENGTH', 0))
form_data = sys.stdin.read(content_length)

print(f"<p><b>Message Body:</b> {form_data}</p>")
print("</body></html>")