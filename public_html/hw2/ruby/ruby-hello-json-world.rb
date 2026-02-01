#!/usr/bin/ruby
require 'json'

# 1. Print Required Headers
puts "Cache-Control: no-cache"
puts "Content-type: application/json"
puts ""

# 2. Get Data
current_time = Time.now
address = ENV['REMOTE_ADDR'] || "Unknown IP"

# 3. Create a Hash (similar to the Perl %message)
message = {
  "title" => "Hello, Ruby!",
  "heading" => "Hello, Ruby!",
  "message" => "This page was generated with the Ruby programming language",
  "time" => current_time.to_s,
  "IP" => address
}

# 4. Convert to JSON string and print
puts JSON.generate(message)