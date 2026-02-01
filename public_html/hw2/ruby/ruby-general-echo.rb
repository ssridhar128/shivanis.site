#!/usr/bin/ruby
require 'json'
require 'date'

# 1. Set Header to JSON
puts "Cache-Control: no-cache"
puts "Content-type: application/json"
puts ""

# 2. Get Request Data
content_length = ENV['CONTENT_LENGTH'].to_i
payload = $stdin.read(content_length)

# 3. Create Hash Map
response = {
  "hostname" => ENV['SERVER_NAME'],
  "datetime" => Time.now.strftime("%Y-%m-%d %H:%M:%S"),
  "user_agent" => ENV['HTTP_USER_AGENT'],
  "IP_address" => ENV['REMOTE_ADDR'],
  "method" => ENV['REQUEST_METHOD'],
  "query_params" => ENV['QUERY_STRING'],
  "payload" => payload
}

# 4. Output as JSON
puts JSON.pretty_generate(response)