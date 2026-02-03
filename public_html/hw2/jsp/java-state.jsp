<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.util.HashMap" %>
<%
    // --- 1. INITIALIZE GLOBAL DATABASE ---
    HashMap<String, String> fpDatabase = (HashMap<String, String>) application.getAttribute("fpDatabase");
    if (fpDatabase == null) {
        fpDatabase = new HashMap<String, String>();
        application.setAttribute("fpDatabase", fpDatabase);
    }

    // --- 2. AJAX REASSOCIATION LOOKUP ---
    String reassociateId = request.getParameter("reassociate_id");
    if (reassociateId != null) {
        String name = fpDatabase.get(reassociateId);
        // Note: We don't restore the session here; we do it in Part 3 via a redirect
        if (name != null) {
            out.print("{\"reassociated\": true, \"name\": \"" + name + "\"}");
        } else {
            out.print("{\"reassociated\": false}");
        }
        return;
    }

    // --- 3. HANDLE RESTORE FROM FINGERPRINT (Sets session and reloads) ---
    String restoreName = request.getParameter("restore_name");
    if (restoreName != null) {
        session.setAttribute("saved_name", restoreName);
        response.sendRedirect("java-state.jsp?restored=1");
        return;
    }

    // --- 4. HANDLE FORM ACTIONS (Set & Clear) ---
    String name = request.getParameter("username");
    String visitorId = request.getParameter("visitorId");
    
    // Save Action
    if (name != null && !name.isEmpty()) {
        session.setAttribute("saved_name", name);
        if (visitorId != null) fpDatabase.put(visitorId, name);
        response.sendRedirect("java-state.jsp");
        return;
    }

    // Clear Action
    if (request.getParameter("clear") != null) {
        session.invalidate();
        // Redirect with signal for the JS popup
        response.sendRedirect("java-state.jsp?just_cleared=true");
        return;
    }

    String savedName = (String) session.getAttribute("saved_name");
    boolean restored = "1".equals(request.getParameter("restored"));
%>

<!DOCTYPE html>
<html>
<head>
    <title>JSP State + Fingerprinting</title>
    <script>
        // Load FingerprintJS
        const fpPromise = import('https://openfpcdn.io/fingerprintjs/v4')
            .then(FingerprintJS => FingerprintJS.load());

        async function initFP() {
            const fp = await fpPromise;
            const result = await fp.get();
            const vid = result.visitorId;

            document.getElementById('visitorIdField').value = vid;
            
            const urlParams = new URLSearchParams(window.location.search);
            const justCleared = urlParams.get('just_cleared') === 'true';
            const currentName = "<%= (savedName != null) ? savedName : "None" %>";

            // Only trigger reassociation if cookies were just cleared
            if (currentName === "None" && justCleared) {
                fetch('java-state.jsp?reassociate_id=' + vid)
                    .then(res => res.json())
                    .then(data => {
                        if (data.reassociated) {
                            document.getElementById('fp-msg').innerText = "Restoring from fingerprint...";
                            // Redirect to restore the session on the server
                            window.location.href = 'java-state.jsp?restore_name=' + encodeURIComponent(data.name);
                        }
                    });
            }
        }
        window.onload = initFP;
    </script>
</head>
<body>
    <h1>JSP State + Fingerprinting</h1>
    <p>Stored Name: <b><%= (savedName != null) ? savedName : "None" %></b></p>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Session</button>
    </form>

    <form method="POST" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Session</button>
    </form>
    
    <p id="fp-msg" style="color: green; font-weight: bold;">
        <%= restored ? "Restored from fingerprint!" : "" %>
    </p>
</body>
</html>