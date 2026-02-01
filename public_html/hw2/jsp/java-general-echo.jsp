<%@ page language="java" contentType="application/json; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.time.LocalDateTime, java.time.format.DateTimeFormatter" %>
<%
    String method = request.getMethod();
    String userAgent = request.getHeader("user-agent");
    String ip = request.getHeader("X-Forwarded-For") != null ? request.getHeader("X-Forwarded-For") : request.getRemoteAddr();
    String now = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
    String sampleData = request.getParameter("sample_data");
%>
{
  "hostname": "<%= request.getServerName() %>",
  "datetime": "<%= now %>",
  "user_agent": "<%= userAgent %>",
  "IP_address": "<%= ip %>",
  "method": "<%= method %>",
  "query_params": {},
  "payload": {
    "language": "jsp",
    "method": "<%= method %>",
    "encoding": "<%= request.getContentType() %>",
    "sample_data": "<%= sampleData != null ? sampleData : "" %>"
  }
}