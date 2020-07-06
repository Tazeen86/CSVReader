# CSVReader
PHP script to read a CSV file and insert records from the file to a postgresql database

this script takes in command line arguments , please make sure that the correct path is used to store the csv file 

the files need to be in your CSVReader folder
example run
tazeen > php user_upload.php --create_table --file <filename> --database <databasename> -u user -p pass -h host
 
 tazeen > php user_upload.php --create_table directive creates a table named users

The script take database name , username , password , filename and inserts the data from the file into a PostgreSQL database

the script creates a table , applies triggers to enforce DATA constraints and checks for email validity and a required name field.

if the email is valid and the name field is provided the script inserts data into the database

in order to get command line help use --help directive

example 
--------
tazeen > php user_upload.php --help

