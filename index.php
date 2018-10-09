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

             echo $argv[1];

         }

    }

}

?>