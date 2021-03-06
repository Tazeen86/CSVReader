<?php
/**
 * Created by PhpStorm.
 * User: tazeen
 * Date: 10/8/18
 * Time: 2:49 PM
 */

//get command line parameters before setting up Database connection

$params=get_option_params();


//Setting up variable to establish DB connection
$dbServername=isset($params['h'])?$params['h']:'localhost';//localhost
$dbUsername= isset($params['u'])?$params['u']:'postgres';//root
$dbPassword =isset($params['p'])?$params['p']:'tazeen';//root
$dbName=isset($params['database'])?$params['database']:'test';//test

if(isset($params['help']))
{
    //Display help text on the Command Line Interface if help switch is given

   display_help();

}
$connection_string = "host=$dbServername user=$dbUsername password=$dbPassword dbname=$dbName";

// establishing a database connection
$GLOBALS['connect'] = pg_connect($connection_string);


if (!$GLOBALS['connect']){

    echo "Database Not Connected \r\n";
}
else
{ 
 if(isset($params['create_table']))
    create_table_structure($params); //create the table
  if(isset($params['file']))
{
   $query="SELECT EXISTS (
   SELECT FROM  information_schema.tables 
   WHERE  table_schema = 'public'
   AND    table_name   = 'users'
   );";

     $result=pg_query($GLOBALS['connect'], $query);
     $if_exists=pg_fetch_assoc($result);
     
      if($if_exists['exists']=='f')  
       { 
         echo " table not created \r\n";
       }
       else
        {
          //triggers have been applied & table is ready for data insertion
               check_file_is_csv($params);
         }
         
       
}
else
 {
     //file parameter has not been provided
     echo "Please provide a filename \r\n";

 }


}


function  get_option_params()
{
    // get command line parameters
    $options="u:p:h:";
    $longOpts=array("file:","database:","dry_run::","help::","create_table::");
    $params=getopt($options,$longOpts);
    return $params;
}

function display_help()
{
   //Display Help in CLI
     echo " This Program takes a CSV file and inserts its content into a PostgresSQL Database \r\n";

    echo "--file Enter the name of CSV file to be processed \r\n";

    echo "--database  Enter the name of your PostgreSQL Database \r\n";

    echo "--create_table Enter this command to create a new table called users\r\n";

    echo "-h  Enter the hostname \r\n";

    echo "-p  Enter the password for your Database \r\n";

    echo "-u  Enter the username for your Database \r\n";

}
function create_table_structure($params)
{
    // when connection is established DROP the table if it already exists
    $query="DROP TABLE IF EXISTS users;";
    pg_query($GLOBALS['connect'], $query);
// Create new users table
    $query= "CREATE TABLE IF NOT EXISTS users( name varchar(80) NOT NULL, surname varchar(80)  , email text NOT NULL UNIQUE CHECK(lower(email)=email)  );";

    $result=pg_query($GLOBALS['connect'], $query);

    if(!$result)
    { // catch error if table is not created
        echo "table not created \r\n";
    }
    else
    {  echo "table has been created\r\n";
        // Add triggers to ensure the values inserted and updated in the respective columns are correct

         $result=create_trigger();

        if(!$result)
        {
            echo "Trigger not working \r\n";
        }
       

    }

}
function check_file_is_csv($params)
{
    
        //Check if the argument passed is a csv file


        $checkForCsv= explode('.',$params['file']);
        if($checkForCsv[1]=='csv')
        {
            //Open the csv file
            $file=fopen("./".$params['file'],'r');
            if($file)
            { //Read file

                file_read($file);


                fclose($file);
            }
            else
            {
                echo "Cannot Open File \r\n";
            }

        }

        else
        {
            echo "the file is not a CSV file \r\n";
        }

}
function create_trigger()
{
   // create triggers to insert and update values properly , with lowercase emails and uppercase name and surname

    $tQuery ="CREATE OR REPLACE FUNCTION  lowercase_email_on_insert() RETURNS trigger AS \$lowercase_email_on_insert$
                                 BEGIN        
                                    NEW.email = LOWER(NEW.email);
                                RETURN NEW;
                                  END;
                  \$lowercase_email_on_insert$ LANGUAGE plpgsql;

                  CREATE TRIGGER lowercase_email_on_insert_trigger BEFORE INSERT OR UPDATE ON users
                  FOR EACH ROW EXECUTE PROCEDURE lowercase_email_on_insert();";
   

     $result= pg_query($GLOBALS['connect'],$tQuery);
    return $result; // return trigger


}
function file_read($file)
{
  //read the file line by line
    while (($user = fgetcsv($file)) !== FALSE) {

        //Obtain the string values of Array Elements
        $name = strval($user[0]);
        $surname = strval($user[1]);
        $email = strval($user[2]);


        if ($name == 'name' && $surname == 'surname' && $email == 'email') {
            //Check for Column Names

        } else {
            /*If Record is A Value Set then
            Check if the Email Address is valid and the name is NOT NULL*/

            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($name)) {

                insert_record_in_table($name,$surname,$email); //insert each record in table

            } else {
                if (empty($name)) {
                    echo "Name of user cannot be NULL \r\n";
                } else {
                    echo "Invalid Email: $email for user $name , Cannot enter email in database\r\n";
                }
            }
        }
    }
}
function insert_record_in_table($name,$surname,$email)
{
   $name=ucfirst(strtolower($name));
   $surname=ucfirst(strtolower($surname));

    $insertStmnt = "INSERT INTO users(name,surname,email) VALUES($$$name$$,$$$surname$$,$$$email$$);";


    if (@pg_query($GLOBALS['connect'], $insertStmnt))
        echo "Record for $name inserted \r\n";
    else
        echo "Error Inserting Record for $name , Check for Duplicates \r\n";

}
?>