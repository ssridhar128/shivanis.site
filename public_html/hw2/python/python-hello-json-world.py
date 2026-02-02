#!/usr/bin/python3
import json
import os
from datetime import datetime


print("Cache-Control: no-cache")
print("Content-Type: application/json\n")

now = datetime.now()
address = os.environ.get('REMOTE_ADDR', 'Unknown IP')

message = {
    "title": "Hello, Python!",
    "heading": "Hello, Python!",
    "message": "This page was generated with the Python programming language",
    "time": now.strftime("%c"),
    "IP": address
}

print(json.dumps(message))