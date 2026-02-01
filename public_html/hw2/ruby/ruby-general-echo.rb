#!/usr/bin/ruby

puts "Cache-Control: no-cache"
puts "Content-type: text/html"
puts ""

puts "<!DOCTYPE html><html><head><title>General Request Echo</title></head>"
puts "<body><h1 align='center'>General Request Echo</h1><hr>"

# 1. Access Environment Variables
puts "<p><b>HTTP Protocol:</b> #{ENV['SERVER_PROTOCOL']}</p>"
puts "<p><b>HTTP Method:</b> #{ENV['REQUEST_METHOD']}</p>"
puts "<p><b>Query String:</b> #{ENV['QUERY_STRING']}</p>"

# 2. Read from Standard Input (Message Body)
content_length = ENV['CONTENT_LENGTH'].to_i
form_data = $stdin.read(content_length)

puts "<p><b>Message Body:</b> #{form_data}</p>"
puts "</body></html>"