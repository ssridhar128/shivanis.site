<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.util.Map" %>
<!DOCTYPE html>
<html>
<head>
    <title>GET Request Echo</title>
</head>
<body>
    <h1 align="center">Get Request Echo</h1>
    <hr>
    <b>Query String:</b> <%= request.getQueryString() %><br />

    <%
        // request.getParameterMap() handles the decoding automatically
        Map<String, String[]> params = request.getParameterMap();
        for (String key : params.keySet()) {
            String value = params.get(key)[0];
    %>
        <%= key %> = <%= value %><br />
    <%
        }
    %>
</body>
</html>