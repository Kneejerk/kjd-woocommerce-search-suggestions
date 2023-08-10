<?php

class KJDWSS_DYM
{
    /**
     * Default list of words to ignore when building the dictionary and making comparisons.
     *
     * TODO: Make this an admin parameter
     *
     * @var array
     */
    public $filter_words = [
        'the','this','that','and','or','for','but','yet','so','lot','of','all','a','some','many','much','any','an',
        'in','with','to','i','you','your','my','me','was','wasnt','were','werent','be','been','it','its','their',
        'them','our','will','wont','can','cant','cannot','could','couldnt','would','wouldnt','should','shouldnt',
        'dont','didnt','does','doesnt','is','isnt','are','arent','have','havent','hasnt','hadnt','aint','us','we',
    ];

    /**
     * This is the only method you should really need here.
     *
     * @param string $search the search query that didn't return any results
     * @param int $acceptance_threshold the percentage of similarity that is acceptable to suggest
     * @return float|int the percentage of similarity between the search query and the suggested query from 0 to 100,
     *                   0 being no match and 100 being an exact match.
     */
    public function suggestions($search, $acceptance_threshold = 75)
    {
        return $this->find_similar_words(
            $search,
            $this->get_site_dictionary(),
            $acceptance_threshold
        );
    }

    /**
     * Compares two strings and returns a percentage of similarity between 0 and 100 (100 being a perfect match).
     *
     * I PHP-ified Ben Yocum's work (https://github.com/ben-yocum/gestalt-pattern-matcher) - thanks Ben!
     *
     * @param string $input1 the first string to compare
     * @param string $input2 the second string to compare
     * @return float|int the percentage of similarity between the two strings
     */
    public function compare_strings(string $input1, string $input2): float
    {
        if ( $input1 == '' || $input2 == '' ) {
            return 0.0;
        }

        // Build the foundation of the stack with our comparison strings...
        $stack = [
            $input1,
            $input2
        ];

        // Set the "score" at zero.
        $score = 0;

        while( count($stack) > 0 ) {
            // Pop the comparison strings off the top...
            $string2 = array_pop($stack);
            $string1 = array_pop($stack);

            // Use a split array of the strings so I don't have to substr with offsets all over the place.
            $string1_split = str_split($string1);
            $string2_split = str_split($string2);

            // Grab the string length once
            $str1_len = strlen($string1);
            $str2_len = strlen($string2);

            // Set up the indicators for sameness
            $sequence_length = 0;
            $sequence_index1 = -1;
            $sequence_index2 = -1;

            // Loop the length of string 1
            for($i = 0; $i < $str1_len; $i++) {
                // Loop the length of string 2
                for($j = 0; $j < $str2_len; $j++) {
                    // Our wildcard to track from our current position out to wherever the strings stop matching
                    $k = 0;
                    while(isset($string1_split[$i+$k]) && isset($string2_split[$j+$k]) && $string1_split[$i+$k] === $string2_split[$j+$k]) {
                        ++$k;
                    }

                    // Now that we've seen how far out $k gets us... Let's see if this is streak-worthy ;)
                    if($k > $sequence_length) {
                        $sequence_length = $k; // Our wildcard that tracks how far out a same-streak goes
                        $sequence_index1 = $i; // Where the streak starts in string 1
                        $sequence_index2 = $j; // Where the streak starts in string 2
                    }
                }
            }

            // We've looped through both strings independently and found ONE similarity, let's tally the score and add more
            // things to look at around where this was.
            if($sequence_length !== 0) {
                // Tally the score based off the sequence length (remember our wildcard $k from before? This is the biggest
                // we saw of that)
                $score += $sequence_length * 2;

                // If our matching sequence didn't start at the beginning of either string, there could be matches here yet.
                if( $sequence_index1 !== 0 && $sequence_index2 !== 0 ) {
                    $stack[] = substr($string1, 0, $sequence_index1);
                    $stack[] = substr($string2, 0, $sequence_index2);
                }

                // If our matching sequence didn't butt up to the end of either string, there are still potential matches.
                if(
                    $sequence_index1 + $sequence_length !== $str1_len
                    && $sequence_index2 + $sequence_length !== $str2_len
                ) {
                    $stack[] = substr($string1, $sequence_index1 + $sequence_length, $str1_len);
                    $stack[] = substr($string2, $sequence_index2 + $sequence_length, $str2_len);
                }
            }
        }

        // This gives us a relative score normalized by the length of the two strings.
        $strlengths = strlen($input1) + strlen($input2);
        $rating = $score / ($strlengths > 0 ? $strlengths : 1); // Habit. Never divide a goose egg.

        // I multiply by 100 here and round to the 5th decimal to give me a Percentage figure. For instance:
        // 12.34567% match is not a great match. 98.76543% match is pretty good! An exact match is 100.
        return round($rating * 100, 5);
    }

    public function make_dictionary()
    {
        $table = JJ::$db->posts;
        $products = JJ::$db->get_results(
            "SELECT post_title, post_content FROM $table WHERE post_type = 'product' AND post_status = 'publish'",
        );

        $filter_words = $this->get_filter_words();

        // $words functions like a set, rather than a list.
        $words = [ // Adding in some basics so we don't start suggesting the wrong gender (ie: "womens stuff" suggests "mens stuff")
            'mens'=>0,
            'womens'=>0,
        ];

        foreach ($products as $product) {
            $product_words = explode(' ', $product->post_title . ' ' . $product->post_content);
            $product_words = array_unique($product_words);
            foreach ( $product_words as $word ) {
                $word = strtolower($word);
                if ( strpos($word, '/') !== false ) {
                    $clean_words = explode('/', $word);
                    foreach ( $clean_words as $clean_word ) {
                        $clean_word = KJDWSS_super_trim($clean_word);
                        if ( strlen($clean_word) < 3 || is_numeric($clean_word) || in_array($clean_word, $filter_words) ) {
                            continue;
                        }
                        $words[$clean_word] = 0;
                    }
                } else {
                    $clean_word = KJDWSS_super_trim($word);
                    if ( strlen($clean_word) < 3 || is_numeric($clean_word) || in_array($clean_word, $filter_words) ) {
                        continue;
                    }
                    $words[$clean_word] = 0; // we don't care how many times it's in there, just that it is.
                }
            }
        }
        $words = array_keys($words);
        return $words;
    }

    public function get_site_dictionary()
    {
        $cache_group = 'jjsc';
        $cache_key = 'jjsc-spell-check-dictionary';
        $words = wp_cache_get( $cache_key, $cache_group );
        if ( !$words || !is_array($words) ) {
            $words = $this->make_dictionary();
            wp_cache_set( $cache_key, $words, $cache_group, DAY_IN_SECONDS );
        }
        return $words;
    }

    public function get_filter_words() // TODO: make this something that can be added to/configured by admins
    {
        // Not exhaustive, but a good start...
        return $this->filter_words;
    }

    /**
     * Takes the search terms and tries to match it against the given dictionary.
     *
     * TODO: Order the dictionary so we can more efficiently compare against more relevant words first.
     *
     * @param string $search the search query that didn't return any results
     * @param array $dictionary the dictionary to compare against
     * @param integer $acceptance_threshold the minimum percentage of similarity that is acceptable to suggest
     * @return string the suggested search query
     */
    public function find_similar_words($search, $dictionary, $acceptance_threshold = 75)
    {
        $search = strtolower(trim($search));
        $filter_words = $this->get_filter_words();
        $did_you_mean = [];
        $search_words = explode(' ', $search);
        $search_words = array_filter($search_words);
        foreach ( $search_words as $search_word ) {
            $search_word = KJDWSS_normalize_string($search_word);
            // check for contractions in the filter list too...
            if ( in_array(str_replace("'",'',$search_word), $filter_words) ) {
                $did_you_mean[] = $search_word;
                continue;
            }
            // BS: Best Suggestion ;)
            $bs = 0;
            $bs_word = '';
            foreach ( $dictionary as $suggestion ) {
                $score = $this->compare_strings($search_word, $suggestion);
                if ( $score === 100 ) {
                    $bs_word = $suggestion;
                    $bs = $score;
                    break;
                } elseif ( $score > $bs ) {
                    $bs_word = $suggestion;
                    $bs = $score;
                }
            }

            $did_you_mean[] = $bs > $acceptance_threshold ? $bs_word : $search_word;
        }

        return join(' ', $did_you_mean);
    }
}
