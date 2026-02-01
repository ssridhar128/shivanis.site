#!/usr/bin/python3
import os, sys, json, datetime

# Change content-type to application/json
print("Cache-Control: no-cache")
print("Content-type: application/json\n")

content_length = int(os.environ.get('CONTENT_LENGTH', 0))
payload = sys.stdin.read(content_length)

# Create a dictionary to match the demo screenshot structure
response = {
    "hostname": os.environ.get('SERVER_NAME'),
    "datetime": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    "user_agent": os.environ.get('HTTP_USER_AGENT'),
    "IP_address": os.environ.get('REMOTE_ADDR'),
    "method": os.environ.get('REQUEST_METHOD'),
    "query_params": os.environ.get('QUERY_STRING'),
    "payload": payload
}

print(json.dumps(response, indent=2))