<?php

    //this is the basic way of getting a database handler from PDO, PHP's built in quasi-ORM
    $dbhandle = new PDO("sqlite:scrabble.sqlite") or die("Failed to open DB");
    if (!$dbhandle) die ($error);
 
    //this is a sample query which gets some data, the order by part shuffles the results
    //the limit 0, 10 takes the first 10 results.
    // you might want to consider taking more results, implementing "pagination", 
    // ordering by rank, etc.
    $query = "SELECT rack FROM racks WHERE length=6 and weight <= 10 order by random() limit 0, 1";
    //$query2 = "SELECT words FROM racks WHERE length <=6"
    //$query = "SELECT rack, words FROM racks WHERE length=6 and weight <= 10 order by random() limit 0, 1";
    
    //this next line could actually be used to provide user_given input to the query to 
    //avoid SQL injection attacks
    $statement = $dbhandle->prepare($query);
    $statement->execute();
    
    //The results of the query are typically many rows of data
    //there are several ways of getting the data out, iterating row by row,
    //I chose to get associative arrays inside of a big array
    //this will naturally create a pleasant array of JSON data when I echo in a couple lines
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    $rack = $results[0]['rack'];
    
    $query2 = "SELECT rack, words FROM racks where rack= :somerack";
    
    $statement2 = $dbhandle->prepare($query2);
    $statement2->bindValue(':somerack', $rack);
    $statement2->execute();
    $results2 = $statement2->fetchAll(PDO::FETCH_ASSOC);
    
    function create_possible_arrays(&$set, &$results)
    {
        for ($i = 0; $i < count($set); $i++)
        {
            $results[] = $set[$i];
            $tempset = $set;
            array_splice($tempset, $i, 1);
            $tempresults = array();
            create_possible_arrays($tempset, $tempresults);
            foreach ($tempresults as $res)
            {
                $results[] = $set[$i] . $res;
            }
        }
    }
    
    function sortString(&$string, &$resultStr) {
        $stringParts = str_split($string);
        sort($stringParts);
        $resultStr = implode('', $stringParts);
    }
    
    $combArr = array();
    $combStr = $results2[0]['rack'];
    $combStr = str_split($combStr);
    create_possible_arrays($combStr, $combArr);
    
    for ($i = 0; $i < count($combArr); $i++) {
        sortString($combArr[$i],$combArr[$i]);
    }
    
    $combArr = array_unique($combArr);
    $combArr = array_values($combArr);
    
    for($i=0; $i < count($combArr); $i++) {
      $queryArr = "SELECT rack, words FROM racks where rack= :somerack";
      $statement3 = $dbhandle->prepare($queryArr);
      $statement3->bindValue(':somerack', $combArr[$i]);
      $statement3->execute();
      $results3 = $statement3->fetchAll(PDO::FETCH_ASSOC);
      $results2=array_merge($results2, $results3);
    }
    
    //this part is perhaps overkill but I wanted to set the HTTP headers and status code
    //making to this line means everything was great with this request
    header('HTTP/1.1 200 OK');
    //this lets the browser know to expect json
    header('Content-Type: application/json');
    //this creates json and gives it back to the browser
    echo json_encode($results2);