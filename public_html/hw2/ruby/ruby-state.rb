#!/usr/bin/ruby
require 'cgi'

cgi = CGI.new
cookie_name = "saved_ruby_name"
# Read current cookie
existing_cookie = cgi.cookies[cookie_name]
saved_name = existing_cookie && existing_cookie.value ? existing_cookie.value[0] : "None"

# Handle actions
new_name = cgi['username']
clear = cgi['clear']

if clear == "true"
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => '', 'expires' => Time.at(0))
  puts cgi.header("status" => "REDIRECT", "location" => "ruby-state.rb", "cookie" => cookie)
  exit
elsif !new_name.empty?
  cookie = CGI::Cookie.new('name' => cookie_name, 'value' => new_name)
  puts cgi.header("status" => "REDIRECT", "location" => "ruby-state.rb", "cookie" => cookie)
  exit
end

# Output Headers and HTML
puts cgi.header("type" => "text/html", "cookie" => cookie, "cache-control" => "no-cache")
puts "<!DOCTYPE html><html><body><h1>Ruby State</h1>"
puts "<p>Stored Name: <b>#{saved_name}</b></p>"
puts "<form method='POST'><input type='text' name='username'><button type='submit'>Save</button></form>"
puts "<form method='POST'><input type='hidden' name='clear' value='true'><button type='submit'>Clear</button></form>"
puts "</body></html>"