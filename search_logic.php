<?php

$search_query = "";
$search_where = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {

    $search_query = trim($_GET['search']);
    

    $words = preg_split('/\s+/u', $search_query); 
    $word_conditions = [];

    foreach ($words as $word) {
        $word = trim($word);
        if (empty($word)) continue;


        $clean_word = mb_strtolower($conn->real_escape_string($word), 'UTF-8');
        

        $word_conditions[] = "(
            LOWER(p.name) LIKE '%$clean_word%' 
            OR LOWER(p.manufacturer) LIKE '%$clean_word%' 
            OR LOWER(p.description) LIKE '%$clean_word%'
            OR LOWER(c.name) LIKE '%$clean_word%'
            OR LOWER(parent.name) LIKE '%$clean_word%'
        )";
    }

    if (!empty($word_conditions)) {

        $search_where = "(" . implode(" AND ", $word_conditions) . ")";
    }
}
?>
