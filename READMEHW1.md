# shivanis.site
Names of Member: Shivani Sridhar

Secret for Grader: here is the link to the (please use your ucsd email I wanted to be safe with it so i made it so that only people with ucsd emails can view this) https://docs.google.com/document/d/1tBb7sCu_d2SM2COxWYApi2ic-09-zeENfpuCLfM1KXs/edit?usp=sharing Password for grader: grader

Links For Grading: homepage with team member info and homework links(please look into Username/password info for logging into the site part of the README because the username and passworf to get into the site are there(username: grader password:grader) https://shivanis.site/ about pages for each team member https://shivanis.site/members/shivanisridhar.html favicon https://shivanis.site/favicon.ico robots.txt https://shivanis.site/robots.txt hw1/hello.php https://shivanis.site/hw1/hello.php hw1/report.html https://shivanis.site/hw1/report.html

Details of Github auto deploy setup: In order to deploy my site onto Github, I created an empty repository on my Github.

Then I made a directory on my local device for cse135 and then a nested directory into this one for hw1. Then I git cloned this public repo that I made into this directory.

I then ran this command:

scp -r user@myIPAddress:/var/www/domain/public_html ~/my/path/to/directory

In order to copy all of the things on my server in my shivanis.site directory into this directory. I then added, committed, and pushed these changes.

I then went into my server and I had to cd .. until var/www/ then i had to give this spot the permissions to change things with this command:

sudo chown -R $USER:root /var/www

After this change I renamed my domain_name directory to domain_name1 as my repo name was my domain_name so that my code will work when I connected my github.

Then I had to git clone the repo into my server using this command and my https link on Github:

sudo git clone myHttpsLinkOnGithub .

After this I deleted the domain_name1 I had after verifying that the site and all of the links worked properly in it.

I then gave the new domain_name all the permissions with:

sudo chown -R $USER:root /var/www/domain_name

Then I made a deploy.sh with the commands that I would need to pull all of the stuff from my github.

Then I wanted to do a test to make sure all of this worked and it did.

I then went into automating the process. This lead me to got to my Github repo, Settings, Security, Secrets and Variables, and Actions.

Under Actions there is a button to add a New repository secret. I then added 3.

In my DO_USER I put the user I have been using to work. Then in DO_HOST I put my IP Address for the project. And in DO_KEY I put my private key that corresponds with the public key I used for this project.

Now when I updated things in my local and pushed it automatically changed it in my server and website too!

Username/password info for logging into the site: username: grader password: grader

Summary of changes to HTML file in DevTools after compression: I did not do anything to make the content compressed. When I checked the DevTools in Chrome it told me that gzip was already there. The compressed files had a smaller transferred size which would improve the performance. The browser automatically decompressed it for the display.

Summary of removing 'server' header: The only thing that worked for me was to go to /etc/apache2/mods-available/security2.conf and add the line SecServerSignature "CSE 135 user".