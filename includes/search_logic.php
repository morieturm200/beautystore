<?php
$search_query = "";
$search_where = "";

if (isset($_GET['search'])) {
    // 1. Очищаємо запит: прибираємо зайві пробіли по боках та плюси
    $search_query = trim(str_replace('+', ' ', $_GET['search']));
    
    if (!empty($search_query)) {
        // 2. Розбиваємо запит на окремі слова (наприклад, "lost cherry" -> ["lost", "cherry"])
        // Це дозволить знайти товар, навіть якщо слова в описі стоять не підряд
        $words = explode(' ', $search_query);
        $word_conditions = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            $clean_word = $conn->real_escape_string($word);

            // 3. Використовуємо LOWER() для ігнорування регістру (хоча MySQL зазвичай робить це сам, так надійніше)
            // Шукаємо кожне слово окремо у назві, бренді або описі
            $word_conditions[] = "(p.name LIKE '%$clean_word%' 
                                  OR p.manufacturer LIKE '%$clean_word%' 
                                  OR p.category LIKE '%$clean_word%'
                                  OR p.description LIKE '%$clean_word%')";
        }

        // 4. Об'єднуємо умови через AND. 
        // Це означає: "знайти товари, де є слово1 І є слово2" (максимальна точність)
        if (!empty($word_conditions)) {
            $search_where = "(" . implode(" AND ", $word_conditions) . ")";
        }
    }
}
?>