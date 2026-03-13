# README.md
## Links:
* Deployed Site: https://reporting.shivanis.site/login.php
    * look at my Grader.md for all of my passwords please
* Repository: https://github.com/ssridhar128/shivanis.site
    * please look into my reporting directory inside the public_html directory to see all of the things that
    went into my reporting.shivanis.site directory on my server

## Technical Particulars of the Project
* used php and MySQL for the backend
* used HTML/CSS with Bootstrap 5 for the frontend
* used Chart.js for the charts
* used QuickChart API for the charts that I could put in my pdf
* used I have a role-based access control
    * super_admin: access all pages, generate reports, comment, manage users, can delete any report
    * analyst: access all pages but users, generate reports, comment, can only delete their own reports
    * viewer: can only see the reports that are generated
* used dompdf and Composer to make the downloadable reports that use HTML, have my reports and analyst and super_admin observations

## Use of AI
I used AI for a lot of debugging. I had a really hard time getting the pdf stuff to start working. 
I had a try catch block in my pdf-helper.php in order to catch any case where the pdf isn't generated.
This kept getting triggered and I was not able to understand why. I asked gemini to help me with this.
Gemini helped me unmask the silent crashes by temporarily stripping out my try/catch blocks so we 
could actually see the fatal errors. Through that, I realized my PHP code was fine. My Linux 
server was the problem. Gemini walked me through using the terminal to install Composer, fix chown
and chmod permissions so the www-data web server wasn't locked out of creating the exports/ 
directory, and grab missing PHP extensions (ext-dom and php-gd) so dompdf could actually parse 
the HTML and draw the charts. It also helped me untangle some super frustrating PHP traps. 
It showed me how to fix a "Headers already sent" crash that was cutting my page in half
by moving my form processing logic above my HTML includes. Later, it taught me how to use Output
Buffering (ob_start()) to trap background warnings that were breaking my POST redirects and causing
ERR_CACHE_MISS errors. Overall, it was great having an AI act as a pair-programmer to explain
why the server was rejecting things instead of just writing code for me.

## Future 
If I had more time to complete this project I would have loved to be able to implement the email send
feature. I would also like to make a thing where the analyst can filter some of the data and then make
a report with this filtered data.