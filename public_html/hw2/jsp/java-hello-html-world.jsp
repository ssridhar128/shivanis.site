<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.util.Date" %>
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

    <%
        // 1. Handle Date and Time
        Date date = new Date();
    %>
    <p>This program was generated at: <%= date.toString() %></p>

    <%
        // 2. Handle IP Address
        // In JSP, the 'request' object is automatically available
        String address = request.getRemoteAddr();
    %>
    <p>Your current IP Address is: <%= address %></p>

</body>
</html>