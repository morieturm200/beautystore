<?php
$search_query = "";
$search_where = "";

if (isset($_GET['search'])) {
    
    $search_query = trim(str_replace('+', ' ', $_GET['search']));
    
    if (!empty($search_query)) {
        
        $words = explode(' ', $search_query);
        $word_conditions = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            $clean_word = $conn->real_escape_string($word);
            $word_conditions[] = "(p.name LIKE '%$clean_word%' 
                                  OR p.manufacturer LIKE '%$clean_word%' 
                                  OR p.category LIKE '%$clean_word%'
                                  OR p.description LIKE '%$clean_word%')";
        }

        if (!empty($word_conditions)) {
            $search_where = "(" . implode(" AND ", $word_conditions) . ")";
        }
    }
}
?>