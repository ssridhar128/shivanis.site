<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%
    // 1. Handle "Set" action
    String name = request.getParameter("username");
    if (name != null && !name.isEmpty()) {
        session.setAttribute("saved_name", name);
        response.sendRedirect("java-state.jsp");
        return;
    }

    // 2. Handle "Clear" action
    if (request.getParameter("clear") != null) {
        session.invalidate();
        response.sendRedirect("java-state.jsp");
        return;
    }

    String savedName = (String) session.getAttribute("saved_name");
%>
<!DOCTYPE html>
<html>
<head><title>JSP State Management</title></head>
<body>
    <h1>JSP Server-Side State</h1>
    <p>Stored Name: <b><%= (savedName != null) ? savedName : "None" %></b></p>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Enter name">
        <button type="submit">Save to Session</button>
    </form>

    <form method="POST" style="margin-top: 10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Session</button>
    </form>
    <br>
    <a href="java-state.jsp">Refresh Page (State should persist)</a>
</body>
</html>