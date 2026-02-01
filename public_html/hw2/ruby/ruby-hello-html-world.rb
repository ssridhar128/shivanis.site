#!/usr/bin/ruby

# 1. Print Required Headers
# Ruby needs the Content-Type followed by a blank line
puts "Cache-Control: no-cache"
puts "Content-type: text/html"
puts "" 

# 2. Print HTML Body
puts "<!DOCTYPE html>"
puts "<html>"
puts "<head>"
puts "    <title>Hello CGI World</title>"
puts "</head>"
puts "<body>"

puts "    <h1 align='center'>Hello Shivani HTML World (Ruby)</h1><hr/>"
puts "    <p>Hello World</p>"
puts "    <p>This page was generated with the Ruby programming language</p>"

# 3. Handle Date and Time
# Time.now is the Ruby equivalent to Perl's localtime
current_time = Time.now
puts "    <p>This program was generated at: #{current_time}</p>"

# 4. Handle IP Address
# ENV is the global hash for environment variables in Ruby
address = ENV['REMOTE_ADDR'] || "Unknown IP"
puts "    <p>Your current IP Address is: #{address}</p>"

puts "</body>"
puts "</html>"