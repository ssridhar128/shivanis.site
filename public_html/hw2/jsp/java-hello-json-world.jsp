<%@ page language="java" contentType="application/json; charset=UTF-8" pageEncoding="UTF-8"%>
{
  "language": "JSP",
  "heading": "Hello, JSP!",
  "message": "This page was generated with the JavaServer Pages programming language",
  "time": "<%= new java.util.Date().toString() %>",
  "IP": "<%= request.getHeader("X-Forwarded-For") != null ? request.getHeader("X-Forwarded-For") : request.getRemoteAddr() %>"
}