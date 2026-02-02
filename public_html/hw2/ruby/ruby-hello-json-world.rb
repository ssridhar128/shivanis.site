#!/usr/bin/ruby
require 'json'

puts "Cache-Control: no-cache"
puts "Content-type: application/json"
puts ""

current_time = Time.now
address = ENV['REMOTE_ADDR'] || "Unknown IP"

message = {
  "title" => "Hello, Ruby!",
  "heading" => "Hello, Ruby!",
  "message" => "This page was generated with the Ruby programming language",
  "time" => current_time.to_s,
  "IP" => address
}

puts JSON.generate(message)