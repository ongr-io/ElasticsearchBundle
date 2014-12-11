# Sugggester types

There are four basic types of suggesters objects to be used in a query and they have different parameters:

* [Term][term_elastic], suggests terms based on edit distance.

| Parameter   | Description                                                     | Setter                |
|-------------|-----------------------------------------------------------------|-----------------------|
| text        | Text to search suggestions for.                                 | constructor, setText  |
| field       | Field to look for suggesting on. This field is mandatory.       | constructor, setField |
| analyzer    | Analyzer to analyse to suggest text with.                       | setAnalyzer           |
| size        | Maximum corrections to be returned per suggest text token.      | setSize               |
| sort        | Defines how suggestions should be sorted per suggest text term. | setSort               |
| suggestMode | Controls what suggestions are included.                         | setSuggestMode        |

* [Phrase][phrase_elastic], basically a more complex term suggester.

| Parameter               | Description                                                                                                   | Setter                     |
|-------------------------|---------------------------------------------------------------------------------------------------------------|----------------------------|
| text                    | Text to search suggestions for.                                                                               | constructor, setText       |
| field                   | Field to look for suggesting on. This field is mandatory.                                                     | constructor, setField      |
| analyzer                | Analyzer to analyse to suggest text with.                                                                     | setAnalyzer                |
| gramSize                | Sets max size of the n-grams (shingles) in the field.                                                         | setGramSize                |
| realWordErrorLikelihood | The likelihood of a term being a misspelled even if the term exists in the dictionary.                        | setRealWordErrorLikelihood |
| confidence              | Defines a factor applied to the input phrases score.                                                          | setConfidence              |
| maxErrors               | The maximum percentage of the terms that at most considered to be misspellings in order to form a correction. | setMaxErrors               |
| highlight               | Setup highlighting. If provided must contain array with keys `pre_tag` and `post_tag`.                        | setHighlight               |
| size                    | Maximum corrections to be returned per suggest text token.                                                    | setSize                    |

* [Completion][completion_elastic], implements basic auto-complete functionality. Requires [special mapping](indexed_suggesters.md).

| Parameter      | Description                                                               | Setter                |
|----------------|---------------------------------------------------------------------------|-----------------------|
| text           | Text to search suggestions for.                                           | constructor, setText  |
| field          | Field to look for suggesting on. This field is mandatory.                 | constructor, setField |
| useFuzzy       | Whether or not to use fuzzy query.                                        | useFuzzy              |
| fuzziness      | The fuzziness factor.                                                     | setFuzziness          |
| transpositions | Sets if transpositions should be counted as one or two changes.           | setTranspositions     |
| minLength      | Minimum length of the input before fuzzy suggestions are returned.        | setMinLength          |
| prefixLength   | Minimum length of the input, which is not checked for fuzzy alternatives. | setPrefixLength       |
| unicodeAware   | Are measurements in unicde format.                                        | setUnicodeAware       |

* [Context][context_elastic], implements auto-complete functionality based on context you provide. Requires [special mapping](indexed_suggesters.md).

| Parameter | Description                                                | Setter                 |
|-----------|------------------------------------------------------------|------------------------|
| text      | Text to search suggestions for.                            | constructor, setText   |
| field     | Field to look for suggesting on. This field is mandatory.  | constructor, setField  |
| context   | Context to look for.                                       | addContext, setContext |
| size      | Maximum corrections to be returned per suggest text token. | setSize                |

Every context is either geo context or category context, they both have value and name parameters, but geo context has an additional precision parameter.

[phrase_elastic]:http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-phrase.html
[term_elastic]:http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-term.html
[completion_elastic]:http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-term.html
[context_elastic]:http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/suggester-context.html
