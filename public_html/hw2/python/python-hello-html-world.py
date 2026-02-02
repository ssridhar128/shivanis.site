#!/usr/bin/python3
import os
from datetime import datetime

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("    <title>Hello CGI World</title>")
print("</head>")
print("<body>")

print("    <h1 align='center'>Hello Shivani HTML World (Python)</h1><hr/>")
print("    <p>Hello World</p>")
print("    <p>This page was generated with the Python programming language</p>")

now = datetime.now()
date_string = now.strftime("%c")
print(f"    <p>This program was generated at: {date_string}</p>")

address = os.environ.get('REMOTE_ADDR', 'Unknown IP')
print(f"    <p>Your current IP Address is: {address}</p>")

print("</body>")
print("</html>")