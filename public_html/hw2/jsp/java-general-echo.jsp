<%@ page language="java" contentType="application/json; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.io.BufferedReader, java.time.LocalDateTime, java.time.format.DateTimeFormatter" %>
<%
    // 1. Get Metadata
    String method = request.getMethod();
    String protocol = request.getProtocol();
    String query = request.getQueryString();
    String userAgent = request.getHeader("user-agent");
    String ip = request.getHeader("X-Forwarded-For") != null ? request.getHeader("X-Forwarded-For") : request.getRemoteAddr();
    String hostname = request.getServerName();
    String now = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));

    // 2. Read Body
    StringBuilder jb = new StringBuilder();
    String line = null;
    try {
        BufferedReader reader = request.getReader();
        while ((line = reader.readLine()) != null)
            jb.append(line);
    } catch (Exception e) { }
    String payload = jb.toString();
%>
{
  "hostname": "<%= hostname %>",
  "datetime": "<%= now %>",
  "user_agent": "<%= userAgent %>",
  "IP_address": "<%= ip %>",
  "method": "<%= method %>",
  "query_params": "<%= query != null ? query : "" %>",
  "payload": "<%= payload %>"
}