#!/usr/bin/python3
import json
import os
from datetime import datetime

# 1. Print Required Headers
print("Cache-Control: no-cache")
print("Content-Type: application/json\n")

# 2. Get Data
now = datetime.now()
# Using os.environ.get is best practice to avoid errors if the key is missing
address = os.environ.get('REMOTE_ADDR', 'Unknown IP')

# 3. Create a Dictionary (similar to the Perl %message)
message = {
    "title": "Hello, Python!",
    "heading": "Hello, Python!",
    "message": "This page was generated with the Python programming language",
    "time": now.strftime("%c"),
    "IP": address
}

# 4. Convert to JSON string and print
print(json.dumps(message))