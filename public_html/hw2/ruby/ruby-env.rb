#!/usr/bin/ruby

puts "Cache-Control: no-cache"
puts "Content-type: text/html"
puts ""

puts "<!DOCTYPE html><html><head><title>Ruby Environment Variables</title></head>"
puts "<body><h1 align='center'>Ruby Environment Variables</h1><hr>"

ENV.keys.sort.each do |key|
  puts "<b>#{key}:</b> #{ENV[key]}<br />"
end

puts "</body></html>"