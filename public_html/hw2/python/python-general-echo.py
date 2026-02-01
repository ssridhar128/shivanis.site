#!/usr/bin/python3
import os
import sys
import json
import datetime
import urllib.parse

# 1. Set Header to JSON
print("Cache-Control: no-cache")
print("Content-type: application/json\n")

# 2. Get Request Metadata
method = os.environ.get('REQUEST_METHOD', 'N/A')
query_string = os.environ.get('QUERY_STRING', '')
content_type = os.environ.get('CONTENT_TYPE', 'N/A')

# 3. Read and Parse the Message Body
content_length = int(os.environ.get('CONTENT_LENGTH', 0))
raw_payload = sys.stdin.read(content_length)

# Parse the payload into a dictionary (e.g., {'sample_data': ['hello']})
parsed_payload = urllib.parse.parse_qs(raw_payload)
# Extract the specific 'sample_data' value
sample_data_value = parsed_payload.get('sample_data', [''])[0]

# 4. Create the nested structure to match the demo
response = {
    "hostname": os.environ.get('SERVER_NAME'),
    "datetime": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    "user_agent": os.environ.get('HTTP_USER_AGENT'),
    "IP_address": os.environ.get('REMOTE_ADDR'),
    "method": method,
    "query_params": urllib.parse.parse_qs(query_string),
    "payload": {
        "language": "python",
        "method": method,
        "encoding": content_type,
        "sample_data": sample_data_value
    }
}

# 5. Output formatted JSON
print(json.dumps(response, indent=2))