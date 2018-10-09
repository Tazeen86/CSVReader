<?php
/**
 * Created by PhpStorm.
 * User: tazeen
 * Date: 10/8/18
 * Time: 2:49 PM
 */


//Setting up variable to establish DB connection
$dbServername="localhost";
$dbUsername= "root";
$dbPassword ="root";
$dbName="test";
$connection_string = "host=$dbServername user=$dbUsername password=$dbPassword dbname=$dbName";

// establishing a database connection
$dbConnect = pg_connect($connection_string);

if (!$dbConnect){

    echo "Database Not Connected";
}
else
{ // when connection is established DROP the table if it already exists
    $query="DROP TABLE IF EXISTS users;";
    pg_query($dbConnect, $query);
// Create new users table
    $query= "CREATE TABLE IF NOT EXISTS users( name varchar(80) NOT NULL CHECK(upper(name)=name), surname varchar(80) CHECK(upper(surname)=surname) , email text NOT NULL UNIQUE CHECK(lower(email)=email)  );";

    $result=pg_query($dbConnect, $query);

    if(!$result)
    { // catch error if table is not created
        echo "table not created";
    }
    else
    {
        // Add triggers to ensure the values inserted and updated in the respective columns are correct

          $tQuery ="
                  CREATE OR REPLACE FUNCTION  lowercase_email_on_insert() RETURNS trigger AS \$lowercase_email_on_insert$
                                 BEGIN        
                                    NEW.email = LOWER(NEW.email);
                                RETURN NEW;
                                  END;
                  \$lowercase_email_on_insert$ LANGUAGE plpgsql;

                  CREATE TRIGGER lowercase_email_on_insert_trigger BEFORE INSERT OR UPDATE ON users
                  FOR EACH ROW EXECUTE PROCEDURE lowercase_email_on_insert();";
          $tQuery.="CREATE OR REPLACE FUNCTION  uppercase_name_on_insert() RETURNS trigger AS \$uppercase_name_on_insert$
                                  BEGIN        
                                       NEW.name = UPPER(NEW.name);
                                  RETURN NEW;
                                  END;
                    \$uppercase_name_on_insert$ LANGUAGE plpgsql;

                  CREATE TRIGGER uppercase_name_on_insert_trigger BEFORE INSERT OR UPDATE ON users
                  FOR EACH ROW EXECUTE PROCEDURE uppercase_name_on_insert();";

          $tQuery.="CREATE OR REPLACE FUNCTION  uppercase_surname_on_insert() RETURNS trigger AS \$uppercase_surname_on_insert$
                                  BEGIN        
                                    NEW.surname = UPPER(NEW.surname);
                                     RETURN NEW;
                                   END;
                     \$uppercase_surname_on_insert$ LANGUAGE plpgsql;

                     CREATE TRIGGER uppercase_surname_on_insert_trigger BEFORE INSERT OR UPDATE ON users
                     FOR EACH ROW EXECUTE PROCEDURE uppercase_surname_on_insert();";

         $result= pg_query($dbConnect,$tQuery);
         if(!$result)
         {
             echo "Trigger not working";
         }
         else
         {  //triggers have been applied & table is ready for data insertion
            if($argc>1)
            {
                //Check if the argument passed is a csv file
                $checkForCsv= array();
                $checkForCsv= explode('.',$argv[1]);
                if($checkForCsv[1]=='csv')
                {
                    //Open the csv file and read it line by line
                    $file = fopen("/var/www/CSVReader/".$argv[1], 'r');
                    while (($user= fgetcsv($file)) !== FALSE) {

                        //Obtain the string values of Array Elements
                        $name=strval($user[0]);
                        $surname=strval($user[1]);
                        $email=strval($user[2]);



                        //if the Email Address is valid

                        if(filter_var($email, FILTER_VALIDATE_EMAIL) && $user[0]!=NULL)
                        {
                            //insert record in Database
                           // echo "Name is $name  Surname is $surname and Email is $email";
                            $insert_stmnt="INSERT INTO users(name,surname,email) VALUES($$$name$$,$$$surname$$,$$$email$$);";
                            $result=pg_query($dbConnect,$insert_stmnt);
                             echo "Record for $name inserted \r\n";

                        }
                        else {
                            echo "Email:$email for the person $name is Invalid , Cannot enter in database\r\n";
                        }


                    }

                    fclose($file);
                }
                else
                {
                    echo "the file is not a CSV file";
                }
            }

         }

    }

}

?>