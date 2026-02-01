<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.util.Map, java.io.BufferedReader" %>
<!DOCTYPE html>
<html>
<head>
    <title>POST Request Echo</title>
</head>
<body>
    <h1 align="center">POST Request Echo</h1>
    <hr>
    
    <b>Message Body:</b><br />
    <ul>
    <%
        // In JSP, the parameters are already parsed for you
        Map<String, String[]> params = request.getParameterMap();
        for (String key : params.keySet()) {
            String value = params.get(key)[0];
    %>
        <li><%= key %> = <%= value %></li>
    <%
        }
    %>
    </ul>
</body>
</html>