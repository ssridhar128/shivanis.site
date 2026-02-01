<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.io.BufferedReader" %>
<!DOCTYPE html>
<html>
<head>
    <title>General Request Echo</title>
</head>
<body>
    <h1 align="center">General Request Echo</h1>
    <hr>
    
    <p><b>HTTP Protocol:</b> <%= request.getProtocol() %></p>
    <p><b>HTTP Method:</b> <%= request.getMethod() %></p>
    <p><b>Query String:</b> <%= request.getQueryString() %></p>

    <p><b>User Agent:</b> <%= request.getHeader("user-agent") %></p>
    <p><b>IP Address:</b> <%= request.getHeader("X-Forwarded-For") != null ? request.getHeader("X-Forwarded-For") : request.getRemoteAddr() %></p>

    <%
        // To read the Message Body in JSP, we must read the input stream
        StringBuilder jb = new StringBuilder();
        String line = null;
        try {
            BufferedReader reader = request.getReader();
            while ((line = reader.readLine()) != null)
                jb.append(line);
        } catch (Exception e) { /* Handle error */ }
    %>
    <p><b>Message Body:</b> <%= jb.toString() %></p>

</body>
</html>