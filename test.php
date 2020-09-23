<?php

//connection with the server
$server = "localhost";
$user = "root";
$password = "";
$database="keywords";

$connection = new mysqli($server, $user, $password, $database);

if ($connection->connect_error) {
  die("ERROR" . $connection->connect_error);
}
echo "Connected ";


if (isset($_POST['test']))
{
    //url from POST super global variable which is used to collect form data after submitting an HTML form
    $url=$_POST["url"];
    echo $url;

//contains of links from the scaning
$found_links= array();

//contains of links after shift an element off the beginning of array
$links= array();

// function to get the keywords from the pages
function getKeywords($url)
{
    $html= @file_get_contents($url);

    $dom= new DOMDocument();
    @$dom->loadHTML($html);

    //an array of all of the pages <meta> tags
    $metas=$dom->getElementsByTagName("meta");

    $keywords="";

    // loop through all of the <meta> tags 
    for ($i = 0; $i < $metas->length; $i++) {
        
        $meta= $metas->item($i);
        
        //check for the keywords
		if (strtolower($meta->getAttribute("name")) == "keywords")
			$keywords = $meta->getAttribute("content");
    }
    //return the keywords
    return $keywords;
}

//recursive function to find all links and sublinks
function recursiveFindLinks ($url)
{
    $html= @file_get_contents($url);

    $dom= new DOMDocument();
    @$dom->loadHTML($html);

    $xpath=new DOMXPath($dom);

    $href=$xpath->evaluate('/html/body//a');

    //giving the function access to global variables
    global $found_links;
    global $links;
    global $connection;

    
    foreach($href as $i)
    {
        $link=$i->getAttribute("href") ;

        //process the links who are like /.....
        if(substr($link,0,1)=='/'&& substr($link,0,2)!='//')
        {
            $link=parse_url($url)["scheme"].'://'.parse_url($url)["host"].$link;
        }
        //process the links who are like //.....
        else if(substr($link,0,2)=='//')
        {
            $link=parse_url($url)["scheme"].':'.$link;
        }
        //process the links who are like ./....
        else if(substr($link,0,2)=='./')
        {
            $link=parse_url($url)["scheme"].'://'.parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($link,1);
        }
        //process the links who are like #
        else if(substr($link,0,1)=='#')
        {
            $link=parse_url($url)["scheme"].'://'.parse_url($url)["host"].parse_url($url)["path"].$link;
        }
        //process the links who are like javascript:
        else if(substr($link,0,11)=='javascript:')
            continue;
        //process the links who are like test.php
        else if (substr($link,0,5)!="https"&&substr($link,0,4)!="http")
        {
            $link=parse_url($url)["scheme"].'://'.parse_url($url)["host"]."/".$link;
        }

        //ff the link isn't in the array add it
        if(!in_array($link,$found_links))
        {
            $found_links[]=$link;
            $links[]=$link;
            //saving the keywords in the variable
            $keyword=getKeywords($link);

            //saving the domain in the domain database table
            $query1="INSERT INTO domain (domain) VALUES ('$link');";
            $result1=$connection->query($query1);
            if(!$result1)
            {
                echo "The domains weren't uploaded in the database";         
            }

            //saving the keywords in the domain database table
            $query2="INSERT INTO keyword (keywords) VALUES ('$keyword');";
            $result2=$connection->query($query2);
            if(!$result2)
            {
                echo "The keywords weren't uploaded in the database";         
            }
    
        } 
        
    }
    //removing an item from the array after we scaned it
    array_shift($links);

    //scanning all links in the array
    foreach ($links as $site)
    {
        recursiveFindLinks($site);
    }
}
    recursiveFindLinks($url);
   
}
?>
<html>
    <body>
        <form action="test.php" method="POST">
        <h1>Input an url</h1>
            <input type="text" name="url" required>
            <input type="submit" name="test" value="SEND">
        </form>
    </body>
</html>
<?php?>