#!/usr/bin/python3
import os
from datetime import datetime

# 1. Print Required Headers
print("Cache-Control: no-cache")
print("Content-Type: text/html\n")  # One \n for the header, one for the gap

# 2. Print HTML Body
print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("    <title>Hello CGI World</title>")
print("</head>")
print("<body>")

print("    <h1 align='center'>Hello Shivani HTML World (Python)</h1><hr/>")
print("    <p>Hello World</p>")
print("    <p>This page was generated with the Python programming language</p>")

# 3. Handle Date and Time
now = datetime.now()
date_string = now.strftime("%c")  # Standard readable format similar to Perl's localtime
print(f"    <p>This program was generated at: {date_string}</p>")

# 4. Handle IP Address
# os.environ.get is safer because it won't crash if the variable is missing
address = os.environ.get('REMOTE_ADDR', 'Unknown IP')
print(f"    <p>Your current IP Address is: {address}</p>")

print("</body>")
print("</html>")