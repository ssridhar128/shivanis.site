<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hello CGI World</title>
</head>
<body>
    <h1 align="center">Hello Shivani HTML World (JSP)</h1><hr/>
    <p>Hello World</p>
    <p>This page was generated with the JavaServer Pages programming language</p>

    <%-- 1. Handle Date and Time using an Expression Tag --%>
    <p>This program was generated at: <%= new java.util.Date().toString() %></p>

    <%-- 2. Handle IP Address --%>
    <p>Your current IP Address is: <%= request.getHeader("X-Forwarded-For") != null ? request.getHeader("X-Forwarded-For") : request.getRemoteAddr() %></p>

</body>
</html>