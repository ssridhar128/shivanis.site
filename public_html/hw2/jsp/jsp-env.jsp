<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.util.Enumeration" %>
<!DOCTYPE html>
<html>
<head>
    <title>JSP Environment (Headers)</title>
</head>
<body>
    <h1 align="center">JSP Request Headers</h1>
    <hr>
    <%
        // JSP/Servlets use a Header enumeration instead of a simple ENV hash
        Enumeration<String> headerNames = request.getHeaderNames();
        while (headerNames.hasMoreElements()) {
            String name = headerNames.nextElement();
            String value = request.getHeader(name);
    %>
        <b><%= name %>:</b> <%= value %><br />
    <%
        }
    %>
</body>
</html>