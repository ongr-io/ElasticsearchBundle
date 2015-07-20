Suggesters
==========

.. toctree::
    :maxdepth: 1
    :titlesonly:
    :hidden:

    types
    indexed_suggesters

..

`Suggesters`_ are used to find a similar looking term using the provided text.

You can use suggesters (in any query, as you would normally) or `suggester api`_.

There are basically four types of suggesters: terms, phrase, completion and context. To find out available parameters
for these suggesters check out `types <./types.html>`_ section.

.. note::
    Completion and context suggesters need indexed data in order to work. To find out how to setup indexing
    and mapping, check `indexed suggesters <./indexed_suggesters.html>`_ section.
..

Using Suggesters in Any Query
-----------------------------

Using suggesters with simple queries, i.e. ``search`` api, is quite simple. You can add suggesters just how you
would add aggregations, filters, etc...

.. code:: php

    $termSuggester = new Suggester(Suggester::TYPE_TERM, 'description', 'distributed');
    $repository = $manager->getRepository('AcmeTestBundle:Product');
    $search = $repository->createSearch();
    $search->addSuggestion($termSuggester);
    $results = $repository->execute($search);

..

That’s it, getting the results is quite simple.

.. code:: php

    <?
    $suggestionsIterator = $results->getSuggestions();

To see how to use the suggestions iterator check out `this <#using-results>`__ section.

Using Suggesters with Suggest API
---------------------------------

.. code:: php

    <?php
    $repository = $manager->getRepository('AcmeTestBundle:Product');
    $termSuggester = new Suggester(Suggester::TYPE_TERM, 'description', 'distributed');
    $phraseSuggester = new Suggester(Suggester::TYPE_PHRASE,  'description', 'distributed');
    // You can pass an array of suggesters or a single suggester as well.
    $suggestionsIterator = $repository->suggest([$termSuggester, $phraseSuggester]);
    $suggestionsIterator2 = $repository->suggest($termSuggester);

..

Using Results
-------------

Once you have the suggestions iterator, you can get all the information needed from it. Multiple suggestions have
one or more suggestion entries based on your analyzer, and each suggestion entry may or may not have multiple
options available. To use this data, you can loop through the iterator, or just access the data you need using indexes.

.. code:: php

    <?php
    foreach ($suggestions as $suggestionEntries) {
        foreach ($suggestionEntries as $suggestionEntry) {
            $suggestionEntryText = $suggestionEntry->getText();
            $suggestionEntryOffset = $suggestionEntry->getOffset();
            $suggestionEntryLength = $suggestionEntry->getLength();
            foreach ($suggestionEntry->getOptions() as $option) {
                $optionText = $option->getText();
                $optionScore = $option->getScore();
            }
        }
    }
    $options = $suggestions['description-term'][0]->getOptions();

.. note:: Note that based on ESB response, an option may be an instance of SimpleOption, CompletionOption, PhraseOption or TermOption.

SimpleOption contains text and score data. Each other type of option extends SimpleOption and stores additional data:

+------------------------+------------------------------------------------------+--------------------+
| Option instance type   | Additional parameter                                 | Getter             |
+========================+======================================================+====================+
| PhraseOption           | ``highlighted``, highlighted text                    | getHighlighted()   |
+------------------------+------------------------------------------------------+--------------------+
| TermOption             | ``frequency``, suggested text document frequency     | getFreq()          |
+------------------------+------------------------------------------------------+--------------------+
| CompletionOption       | ``payload``, returns payload of a suggest document   | getPayload()       |
+------------------------+------------------------------------------------------+--------------------+

.. note:: Note, just because you’re using, for example, completion suggester, doesn’t mean you’ll get completion options, if payload was not set, you’ll get a simple option instead. Same goes for other options.

.. _Suggesters: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters.html
.. _suggester api: http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-suggesters.html
