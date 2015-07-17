Suggester Types
===============

There are four basic types of suggester objects to be used in a query and they have different parameters,
text and field parameters have own argument in constructor, other parameters should be passed as array.

`Term`_, suggests terms based on edit distance.

+---------------+-------------------------------------------------------------------+
| Parameter     | Description                                                       |
+===============+===================================================================+
| text          | Text to search suggestions for.                                   |
+---------------+-------------------------------------------------------------------+
| field         | Field to look for suggesting on. This field is mandatory.         |
+---------------+-------------------------------------------------------------------+
| analyzer      | Analyzer to analyse to suggest text with.                         |
+---------------+-------------------------------------------------------------------+
| size          | Maximum corrections to be returned per suggest text token.        |
+---------------+-------------------------------------------------------------------+
| sort          | Defines how suggestions should be sorted per suggest text term.   |
+---------------+-------------------------------------------------------------------+
| suggestMode   | Controls what suggestions are included.                           |
+---------------+-------------------------------------------------------------------+

`Phrase`_, basically a more complex term suggester.

+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| Parameter                 | Description                                                                                                     |
+===========================+=================================================================================================================+
| text                      | Text to search suggestions for.                                                                                 |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| field                     | Field to look for suggesting on. This field is mandatory.                                                       |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| analyzer                  | Analyzer to analyse to suggest text with.                                                                       |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| gramSize                  | Sets max size of the n-grams (shingles) in the field.                                                           |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| realWordErrorLikelihood   | The likelihood of a term being a misspelled even if the term exists in the dictionary.                          |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| confidence                | Defines a factor applied to the input phrases score.                                                            |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| maxErrors                 | The maximum percentage of the terms that at most considered to be misspellings in order to form a correction.   |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| highlight                 | Setup highlighting. If provided must contain array with keys ``pre_tag`` and ``post_tag``.                      |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+
| size                      | Maximum corrections to be returned per suggest text token.                                                      |
+---------------------------+-----------------------------------------------------------------------------------------------------------------+

`Completion`_, implements basic auto-complete functionality. Requires `<indexed_suggesters.rst>`_.

+------------------+-----------------------------------------------------------------------------+
| Parameter        | Description                                                                 |
+==================+=============================================================================+
| text             | Text to search suggestions for.                                             |
+------------------+-----------------------------------------------------------------------------+
| field            | Field to look for suggesting on. This field is mandatory.                   |
+------------------+-----------------------------------------------------------------------------+
| useFuzzy         | Whether or not to use fuzzy query.                                          |
+------------------+-----------------------------------------------------------------------------+
| fuzziness        | The fuzziness factor.                                                       |
+------------------+-----------------------------------------------------------------------------+
| transpositions   | Sets if transpositions should be counted as one or two changes.             |
+------------------+-----------------------------------------------------------------------------+
| minLength        | Minimum length of the input before fuzzy suggestions are returned.          |
+------------------+-----------------------------------------------------------------------------+
| prefixLength     | Minimum length of the input, which is not checked for fuzzy alternatives.   |
+------------------+-----------------------------------------------------------------------------+
| unicodeAware     | Are measurements in unicde format.                                          |
+------------------+-----------------------------------------------------------------------------+

`Context`_, implements auto-complete functionality based on context you provide. Requires `<indexed_suggesters.rst>`_.

+-------------+--------------------------------------------------------------+
| Parameter   | Description                                                  |
+=============+==============================================================+
| text        | Text to search suggestions for.                              |
+-------------+--------------------------------------------------------------+
| field       | Field to look for suggesting on. This field is mandatory.    |
+-------------+--------------------------------------------------------------+
| context     | Context to look for.                                         |
+-------------+--------------------------------------------------------------+
| size        | Maximum corrections to be returned per suggest text token.   |
+-------------+--------------------------------------------------------------+

Every context is either geo context or category context, they both have value and name parameters, but geo context
has an additional precision parameter.

Parameter usage example.
------------------------

.. code:: php

    $phraseSuggester = new Suggester(
        Suggester::TYPE_PHRASE,
        'description',
        'distributed',
        ['confidence' => 0.8, 'maxErrors' => 5],
    );

    $termSuggester = new Suggester(Suggester::TYPE_TERM, 'description', 'distributed');
    $termSuggester->addParameter('size', 5);
    $termSuggester->addParameter('sort', 'score');

    $termSuggester2 = new Suggester(Suggester::TYPE_TERM, 'description', 'more');
    $termSuggester2->setParameters(['size' => 5, 'sort' => 'frequency']);

..

.. _Term: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-term.html
.. _Phrase: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-phrase.html
.. _Completion: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters-term.html
.. _Context: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/suggester-context.html
