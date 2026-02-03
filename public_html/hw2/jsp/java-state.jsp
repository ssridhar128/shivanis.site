<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.io.*" %>
<%@ page import="java.util.*" %>
<%@ page import="java.net.URLEncoder" %>
<%@ page import="java.net.URLDecoder" %>
<%!
    // Method to read fingerprint database
    public Map<String, String> readFpDb(String dbFilePath) {
        Map<String, String> db = new HashMap<>();
        File file = new File(dbFilePath);
        if (!file.exists()) return db;
        BufferedReader reader = null;
        try {
            reader = new BufferedReader(new FileReader(file));
            String line;
            while ((line = reader.readLine()) != null) {
                String[] parts = line.split("\\|");
                if (parts.length == 2) {
                    db.put(parts[0], parts[1]);
                }
            }
        } catch (Exception e) {
            // ignore
        } finally {
            try { if (reader != null) reader.close(); } catch (Exception e) {}
        }
        return db;
    }
    
    // Method to save to database
    public void writeToDb(String dbFilePath, String vid, String name) {
        BufferedWriter bw = null;
        try {
            bw = new BufferedWriter(new FileWriter(dbFilePath, true));
            bw.write(vid + "|" + name);
            bw.newLine();
        } catch (Exception e) {
            // ignore
        } finally {
            try { if (bw != null) bw.close(); } catch (Exception e) {}
        }
    }
%>
<%
    // Database file path
    String dbFile = application.getRealPath("/hw2/fp_database.txt");
    
    // Handle reassociation lookup (returns JSON)
    String reassociateId = request.getParameter("reassociate_id");
    if (reassociateId != null && !reassociateId.isEmpty()) {
        response.setContentType("application/json");
        response.setCharacterEncoding("UTF-8");
        Map<String, String> db = readFpDb(dbFile);
        if (db.containsKey(reassociateId)) {
            String foundName = db.get(reassociateId).replace("\\", "\\\\").replace("\"", "\\\"");
            out.print("{\"reassociated\":true,\"name\":\"" + foundName + "\"}");
        } else {
            out.print("{\"reassociated\":false}");
        }
        return;
    }
    
    // Handle restore from fingerprint
    String restoreName = request.getParameter("restore_name");
    if (restoreName != null && !restoreName.isEmpty()) {
        session.setAttribute("saved_name", restoreName);
%>
<!DOCTYPE html>
<html>
<head><title>JSP State + Fingerprinting</title></head>
<body>
    <h1>JSP State + Fingerprinting</h1>
    <p>Stored Name: <b><%= restoreName %></b></p>
    
    <form method="POST" action="java-state.jsp">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Session</button>
    </form>

    <form method="POST" action="java-state.jsp" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Session</button>
    </form>
    
    <p style="color: green; font-weight: bold;">Restored from fingerprint!</p>
    
    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        
        async function initFP() {
            const cdnSources = [
                'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://unpkg.com/@fingerprintjs/fingerprintjs@4/dist/fp.min.js'
            ];
            
            for (const src of cdnSources) {
                try {
                    await loadScript(src);
                    if (typeof FingerprintJS !== 'undefined') break;
                } catch (e) {}
            }
            
            if (typeof FingerprintJS !== 'undefined') {
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                document.getElementById('visitorIdField').value = result.visitorId;
            }
        }
        
        document.addEventListener('DOMContentLoaded', initFP);
    </script>
</body>
</html>
<%
        return;
    }

    // Handle "Set" action
    String name = request.getParameter("username");
    String visitorId = request.getParameter("visitorId");
    if (name != null && !name.isEmpty()) {
        // Save to fingerprint database
        if (visitorId != null && !visitorId.isEmpty()) {
            writeToDb(dbFile, visitorId, name);
        }
        session.setAttribute("saved_name", name);
        response.sendRedirect("java-state.jsp");
        return;
    }

    // Handle "Clear" action
    if (request.getParameter("clear") != null) {
        // Remove the attribute first, then invalidate
        session.removeAttribute("saved_name");
        session.invalidate();
        // Get a new session to avoid issues
        request.getSession(true);
        response.setStatus(HttpServletResponse.SC_FOUND);
        response.setHeader("Location", "java-state.jsp?just_cleared=true");
        response.setHeader("Cache-Control", "no-cache, no-store, must-revalidate");
        return;
    }

    // Check if just cleared (new session after invalidation)
    String justClearedParam = request.getParameter("just_cleared");
    boolean isJustCleared = "true".equals(justClearedParam);
    
    String savedName = (String) session.getAttribute("saved_name");
    String displayName = (savedName != null && !savedName.isEmpty()) ? savedName : "None";
%>
<!DOCTYPE html>
<html>
<head>
    <title>JSP State + Fingerprinting</title>
    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }
        
        async function initFP() {
            const cdnSources = [
                'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://unpkg.com/@fingerprintjs/fingerprintjs@4/dist/fp.min.js',
                'https://openfpcdn.io/fingerprintjs/v4/iife.min.js'
            ];
            
            let loaded = false;
            for (const src of cdnSources) {
                try {
                    await loadScript(src);
                    if (typeof FingerprintJS !== 'undefined') {
                        loaded = true;
                        break;
                    }
                } catch (e) {
                    console.log("Failed to load from:", src);
                }
            }
            
            if (!loaded || typeof FingerprintJS === 'undefined') {
                console.error("Could not load FingerprintJS");
                return;
            }
            
            try {
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                const vid = result.visitorId;

                console.log("1. Fingerprint loaded, visitorId:", vid);
                document.getElementById('visitorIdField').value = vid;
                
                const urlParams = new URLSearchParams(window.location.search);
                const justCleared = urlParams.get('just_cleared') === 'true';
                const savedName = "<%= displayName %>";
                
                console.log("2. justCleared:", justCleared, "savedName:", savedName);

                if ((savedName == "None" || savedName == "") && justCleared) {
                    console.log("3. Conditions met, fetching reassociate_id...");
                    fetch('java-state.jsp?reassociate_id=' + vid)
                        .then(res => res.json())
                        .then(data => {
                            console.log("4. Server response:", data);
                            if (data.reassociated) {
                                console.log("5. Reassociated! Redirecting to restore_name...");
                                document.getElementById('fp-msg').innerText = "Restoring from fingerprint...";
                                window.location.href = 'java-state.jsp?restore_name=' + encodeURIComponent(data.name);
                            } else {
                                console.log("5. NOT reassociated - fingerprint not found in database");
                                document.getElementById('fp-msg').innerText = "Fingerprint not found in database";
                                document.getElementById('fp-msg').style.color = "red";
                            }
                        })
                        .catch(err => {
                            console.error("4. Fetch error:", err);
                        });
                } else {
                    console.log("3. Conditions NOT met for reassociation");
                }
            } catch (e) {
                console.error("FingerprintJS error:", e);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Only attach validation to the save form, not the clear form
            const saveForm = document.getElementById('saveForm');
            if (saveForm) {
                saveForm.addEventListener('submit', function(e) {
                    const vid = document.getElementById('visitorIdField').value;
                    if (!vid) {
                        alert('Please wait for fingerprint to load before submitting');
                        e.preventDefault();
                    }
                });
            }
            
            initFP();
        });
    </script>
</head>
<body>
    <h1>JSP State + Fingerprinting</h1>
    <p>Stored Name: <b><%= displayName %></b></p>
    
    <form method="POST" id="saveForm">
        <input type="text" name="username" placeholder="Enter name">
        <input type="hidden" name="visitorId" id="visitorIdField">
        <button type="submit">Save to Session</button>
    </form>

    <form method="POST" id="clearForm" style="margin-top:10px;">
        <input type="hidden" name="clear" value="true">
        <button type="submit">Clear Session</button>
    </form>
    
    <p id="fp-msg" style="color: green; font-weight: bold;"></p>
    <br>
    <a href="java-state.jsp">Refresh Page (State should persist)</a>
</body>
</html>
