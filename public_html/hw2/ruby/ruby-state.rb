#!/usr/bin/ruby
require 'cgi'

cgi = CGI.new
cookie_name = "saved_ruby_name"

# 1. Read existing cookie
existing_cookie = cgi.cookies[cookie_name]
saved_name = (existing_cookie && !existing_cookie.empty?) ? existing_cookie[0] : "None"

# 2. Handle Logic
new_name = cgi['username']
clear = cgi['clear']

if clear == "true"
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => '', 'expires' => Time.at(0))
  puts cgi.header("status" => "REDIRECT", "location" => "ruby-state.rb", "cookie" => cookie)
  exit
elsif !new_name.to_s.empty?
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => new_name)
  puts cgi.header("status" => "REDIRECT", "location" => "ruby-state.rb", "cookie" => cookie)
  exit
end

# 3. Output Headers and HTML
# Only send the cookie header if the cookie exists to avoid 500 errors
header_options = { "type" => "text/html", "cache-control" => "no-cache" }
header_options["cookie"] = existing_cookie if existing_cookie && !existing_cookie.empty?

puts cgi.header(header_options)
puts "<!DOCTYPE html><html><body><h1>Ruby State</h1>"
puts "<p>Stored Name: <b>#{saved_name}</b></p>"
puts "<form method='POST'><input type='text' name='username'><button type='submit'>Save</button></form>"
puts "<form method='POST'><input type='hidden' name='clear' value='true'><button type='submit'>Clear</button></form>"
puts "</body></html>"