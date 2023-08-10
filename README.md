# Woocommerce Search Suggestions

by Kneejerk Development - the penultimate "did you mean" plugin for Woocommerce!

*...and yes, I'm absolutely aware of what "penultimate" means.*

## Requirements

- PHP 8.0 or greater
- WordPress 6.0 or greater
- Woocommerce 8.0 or greater
- **Object Caching: Enabled** - this is not a joke.

## How does it work?

You're not going to believe me... there are no API's, no AI, and no real magic.

KJDWSS works by hooking into the `woocommerce_no_products_found` hook, and simply adding a paragraph of text,
indicating possible suggestions for the user's search.

It first builds a dictionary of all your products' using their titles and content, then normalizes each word. After it
has the dictionary, it takes the search query and compares it against all the dictionary words, returning a percentage
of similarity.

If it finds a word above the acceptance threshold (default: 75% match), it swaps the dictionary word out from the
queried words.

## Performance Implications

Current metrics show this adds less than 1s of load time to the "no products found" search request, and ~10mb added
memory usage. As each ecom site is different, your results may vary.

## Hooks & Filters

### Getting a Suggestion

The most important filter to worry about is the `kjdwss-get-suggestion` filter. You can pass any string through that
filter, and on priority 10, KJDWSS will do a suggestion replacement. It would look something like this:

```
$suggestion = apply_filters('kjdwss-get-suggestion', "Nachos, Bananas, and Tequila!" );
// depending on YOUR products, you might see: Did you mean: macho banana and tequila
```

Priority 10 allows you to do any replacements before KJDWSS gets a swing at it, but also afterwards.

### Setting the Acceptance Threshold

The acceptance threshold is a percentage value (from 0 - 100) regarding how close of a match is required. You can
update this value using the `kjdwss-acceptance-threshold` filter.

```
// Ensure we have an 88% match before we replace anything!
add_filter('kjdwss-acceptance-threshold', function($threshold) { return 88; } );
```

It does not replace words where the match is lower than the threshold, but once it is reached, it continues searching
the rest of the dictionary for a better match. If.... she can't find a better match (did someone say Pearl Jam?), it
uses the highest matching word to replace.

### Last chance to modify the Output

The html output is also filtered. Using the `kjdwss-output` filter, you have the entire html string that you can
replace, modify, etc...

```
add_filter('kjdwss-output', function($output) { return str_replace("Did you mean:", "Have you tried:", $output); });
```

Here the filter modifies the iconic "Did you mean:" phrase with "Have you tried:" -- now, there are more efficient ways
to handle this, but this is the stage the plugin is at currently. Tada!

## Styling

Using the WP Appearance Customizer, you can add some basic CSS to style the output. I might suggest something like
this:

```
.kjdwss-did-you-mean { /* this styles the entire paragraph */
    font-size: 1.2rem;
}
.kjdwss-lead-in { /* The span that holds the phrase "Did you mean:" */
    font-weight: bold;
}
.kjdwss-suggestion { /* this is the Anchor tag that holds the new search url */
    text-decoration: underline;
    color: blue;
}
```

Those are the three CSS classes the output provides by default. Through the use of the output filter mentioned above,
you can completely replace the output.

## Roadmap

More features are intended to help site admins configure additional settings; this plugin is intended to be extremely
minimal, but it would be nice to allow admins to manage their dictionaries, the template output, and the acceptance
threshold via a UI.

For now, you have the hooks!
