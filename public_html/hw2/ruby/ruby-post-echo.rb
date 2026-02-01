#!/usr/bin/ruby
require 'cgi'

puts "Cache-Control: no-cache"
puts "Content-type: text/html"
puts ""

cgi = CGI.new
# Read raw stdin for the "Message Body" display
content_length = ENV['CONTENT_LENGTH'].to_i
post_data = $stdin.read(content_length)

puts "<!DOCTYPE html><html><head><title>POST Request Echo</title></head>"
puts "<body><h1 align='center'>POST Request Echo</h1><hr>"

puts "<b>Message Body:</b> #{post_data}<br />"
puts "<ul>"

# Loop through parsed parameters
cgi.params.each do |key, values|
  puts "<li>#{key} = #{values[0]}</li>"
end

puts "</ul></body></html>"