#!/usr/bin/ruby
require 'cgi'

puts "Cache-Control: no-cache"
puts "Content-type: text/html"
puts ""

cgi = CGI.new
query_string = ENV['QUERY_STRING'] || ""

puts "<!DOCTYPE html><html><head><title>GET Request Echo</title></head>"
puts "<body><h1 align='center'>Get Request Echo</h1><hr>"

puts "<b>Query String:</b> #{query_string}<br />"

# Loop through parameters
cgi.params.each do |key, values|
  puts "#{key} = #{values[0]}<br />"
end

puts "</body></html>"