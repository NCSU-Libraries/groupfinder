Notice
======
GroupFinder was decommissioned at NCSU on December 17, 2015 and is no longer being supported. 

GroupFinder
===========

GroupFinder is a system designed to notify groups of meeting places in real time. Fill out a simple web form, and your meeting information is available on the project's home page. A large read only display, suitable for electronic signage, is also included.

GroupFinder uses a MySQL database, php for logic, and jQuery/jQuery UI for display. It was built to help ad-hoc and planned student study groups meet up in large buildings where cell phone reception can be challenging.

Terms Of Use
===========
    
MIT/X11 License
See included LICENSE.txt or http://www.opensource.org/licenses/mit-license.php
    
Installation
===========

1.	Create a mySQL database using the included .sql file. 4 sample locations are included in the database.
		
2. Copy everything in the app directory to a directory on your webserver. (Referred to as /groupfinder hereafter)

3. Edit /groupfinder/includes/config.php. 
3.1 Fill in appropriate values in the DATABASE CONFIGURATION section.
3.2 Enter your email address as a value for the $moderators and $internalEmail variables. 
3.3 Enter the path and filename of your log file in the $logfile variable. The file must be writeable by php in this location.

4. Edit /groupfinder/includes/functions.php.
4.1 Edit the getCurrentUser() function to make it return the "test" value, as noted in the comment. 
Later on, add your own authentication code here if desired.

5. Visit /groupfinder in your web browser. You should see the main interface. On the top right side, you should see 'Logged in as test.' On the left side, you should see 2 yellow links marked 'Manage Posts' and 'View Statistics.' Yellow links are visible to admin users only (admin = 1) in user table. Admin privileges should not be granted to standard users. Included user creation function does not grant these privileges. 

6. Post a test post. You should then be able to disable the post if desired on the 'Manage Posts' page and see it in 'View Statistics.'

7. Post another test post. Mouse over it and choose 'Report.' You should receive an email. This is a feature so that people can report abusive uses of the system for administrative review.

8. View /groupfinder/eboard.php. This is a file for read-only display of activities on a large display. It was set up for use in Safari on a 1440x900 display. 


Notes
===========

See http://www.lib.ncsu.edu/groupfinder for a production example.
				
Adding additional locations or removing the sample locations must be done with SQL; there is no interface.
		



	

