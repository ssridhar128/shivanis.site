#!/usr/bin/ruby
require 'json'
require 'date'
require 'cgi'

puts "Cache-Control: no-cache"
puts "Content-type: application/json\n\n"

cgi = CGI.new
content_length = ENV['CONTENT_LENGTH'].to_i
raw_payload = $stdin.read(content_length)

# Create the nested structure from the screenshot
response = {
  "hostname" => ENV['SERVER_NAME'],
  "datetime" => Time.now.strftime("%Y-%m-%d %H:%M:%S"),
  "user_agent" => ENV['HTTP_USER_AGENT'],
  "IP_address" => ENV['REMOTE_ADDR'],
  "method" => ENV['REQUEST_METHOD'],
  "query_params" => CGI.parse(ENV['QUERY_STRING'] || ""),
  "payload" => {
    "language" => "ruby",
    "method" => ENV['REQUEST_METHOD'],
    "encoding" => ENV['CONTENT_TYPE'],
    "sample_data" => cgi['sample_data']
  }
}

puts JSON.pretty_generate(response)